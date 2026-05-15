<?php
/**
 * Creates a ludoteca table booking with row-level concurrency control.
 *
 * Concurrency model: a MySQL named lock (GET_LOCK) keyed by (location, date)
 * serializes all booking attempts on the same day at the same store. This
 * makes the availability re-check + insert atomic without a costly schema
 * (e.g., a per-(location, date) lock row). The lock is released in finally
 * so it does not survive a rollback.
 *
 * Validations performed:
 *   - Customer is logged in (caller passes a non-null customerId).
 *   - Booking date is within the advance-days window for this customer.
 *   - Every time slot belongs to the location, is active, and is valid for
 *     the date's day of the week.
 *   - tables_count >= 1 (no upper bound enforced here; capacity check below
 *     does the right thing).
 *   - Sum(tables_count) per slot does not exceed the slot's free capacity.
 */

namespace Zaca\Events\Service\Ludoteca;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Zaca\Events\Helper\Data as Helper;
use Zaca\Events\Service\Ludoteca\Dto\BookingRequest;

class ReservationCreator
{
    private const LOCK_TIMEOUT_SECONDS = 5;

    private ResourceConnection $resource;
    private AvailabilityService $availability;
    private Helper $helper;

    public function __construct(
        ResourceConnection $resource,
        AvailabilityService $availability,
        Helper $helper
    ) {
        $this->resource = $resource;
        $this->availability = $availability;
        $this->helper = $helper;
    }

    /**
     * @return int booking_id
     * @throws LocalizedException
     */
    public function create(BookingRequest $request): int
    {
        $this->validateBasics($request);

        $connection = $this->resource->getConnection();
        $lockName = sprintf(
            'zaca_ludoteca:%d:%s',
            $request->locationId,
            $request->bookingDate->format('Y-m-d')
        );

        $acquired = (int) $connection->fetchOne('SELECT GET_LOCK(?, ?)', [
            $lockName,
            self::LOCK_TIMEOUT_SECONDS,
        ]);
        if ($acquired !== 1) {
            throw new LocalizedException(
                __('No se ha podido bloquear ese día para reservar. Inténtalo de nuevo.')
            );
        }

        try {
            $connection->beginTransaction();
            try {
                $this->validateAgainstCurrentAvailability($request);
                $bookingId = $this->insertBooking($request);
                $this->insertSlots($bookingId, $request);
                $connection->commit();
                return $bookingId;
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
        } finally {
            $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function validateBasics(BookingRequest $request): void
    {
        if ($request->customerId <= 0) {
            throw new LocalizedException(__('Tienes que iniciar sesión para reservar.'));
        }
        if (empty($request->slots)) {
            throw new LocalizedException(__('Selecciona al menos un turno.'));
        }
        if (trim($request->phoneNumber) === '') {
            throw new LocalizedException(__('Indica un teléfono de contacto.'));
        }

        $today = new \DateTimeImmutable('today');
        if ($request->bookingDate < $today) {
            throw new LocalizedException(__('No puedes reservar en una fecha pasada.'));
        }

        $maxAdvance = $this->helper->getMaxAdvanceDays($request->customerId);
        $maxBookableDate = $today->modify('+' . max(0, $maxAdvance - 1) . ' days');
        if ($request->bookingDate > $maxBookableDate) {
            throw new LocalizedException(
                __('Esta fecha sólo está disponible para socios del Club Zacatrus.')
            );
        }

        $isClub = $this->helper->isClubMember($request->customerId);
        if (!$isClub) {
            if (count($request->slots) > 1) {
                throw new LocalizedException(
                    __('Sólo los socios del Club pueden reservar más de un turno a la vez.')
                );
            }
            if ($this->countActiveSlotsForCustomer($request->customerId) > 0) {
                throw new LocalizedException(
                    __('Ya tienes una reserva activa. Cancélala o únete al Club Zacatrus para reservar más de un turno.')
                );
            }
        }

        // Detect duplicate time slots in the same request.
        $seenSlotIds = [];
        foreach ($request->slots as $slot) {
            if ($slot->tablesCount < 1) {
                throw new LocalizedException(__('Cada turno debe reservar al menos una mesa.'));
            }
            if (isset($seenSlotIds[$slot->timeSlotId])) {
                throw new LocalizedException(__('No puedes repetir el mismo turno en una reserva.'));
            }
            $seenSlotIds[$slot->timeSlotId] = true;
        }
    }

    /**
     * Count of confirmed slot lines for this customer with booking_date >= today.
     */
    private function countActiveSlotsForCustomer(int $customerId): int
    {
        $connection = $this->resource->getConnection();
        $bookingSlot = $this->resource->getTableName('zaca_events_table_booking_slot');
        $booking = $this->resource->getTableName('zaca_events_table_booking');

        return (int) $connection->fetchOne(
            $connection->select()
                ->from(['s' => $bookingSlot], ['count' => new \Zend_Db_Expr('COUNT(*)')])
                ->joinInner(['b' => $booking], 'b.booking_id = s.booking_id', [])
                ->where('b.customer_id = ?', $customerId)
                ->where('b.status = ?', 'confirmed')
                ->where('s.booking_date >= ?', (new \DateTimeImmutable('today'))->format('Y-m-d'))
        );
    }

    private function validateAgainstCurrentAvailability(BookingRequest $request): void
    {
        $available = $this->availability->availabilityForDate(
            $request->locationId,
            $request->bookingDate,
            $request->customerId
        );
        $byId = [];
        foreach ($available as $row) {
            $byId[$row['time_slot_id']] = $row;
        }

        foreach ($request->slots as $slot) {
            if (!isset($byId[$slot->timeSlotId])) {
                throw new LocalizedException(
                    __('Uno de los turnos seleccionados ya no está disponible para esta fecha.')
                );
            }
            $info = $byId[$slot->timeSlotId];
            if ($slot->tablesCount > $info['free_tables']) {
                throw new LocalizedException(
                    __(
                        'En el turno %1 - %2 quedan %3 mesa(s) libres y has pedido %4.',
                        $info['start_time'],
                        $info['end_time'],
                        $info['free_tables'],
                        $slot->tablesCount
                    )
                );
            }
        }
    }

    private function insertBooking(BookingRequest $request): int
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('zaca_events_table_booking');

        $connection->insert($table, [
            'location_id' => $request->locationId,
            'customer_id' => $request->customerId,
            'booking_date' => $request->bookingDate->format('Y-m-d'),
            'status' => 'confirmed',
            'phone_number' => substr(trim($request->phoneNumber), 0, 20),
            'unsubscribe_code' => $this->generateUnsubscribeCode(),
        ]);
        return (int) $connection->lastInsertId($table);
    }

    private function insertSlots(int $bookingId, BookingRequest $request): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('zaca_events_table_booking_slot');

        $rows = [];
        foreach ($request->slots as $slot) {
            $rows[] = [
                'booking_id' => $bookingId,
                'location_id' => $request->locationId,
                'booking_date' => $request->bookingDate->format('Y-m-d'),
                'time_slot_id' => $slot->timeSlotId,
                'tables_count' => $slot->tablesCount,
            ];
        }
        $connection->insertMultiple($table, $rows);
    }

    private function generateUnsubscribeCode(): string
    {
        return bin2hex(random_bytes(16));
    }
}
