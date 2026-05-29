<?php
/**
 * Computes ludoteca table availability per (location, date, time slot).
 *
 * Cross-checks two sources of occupancy:
 *   - Confirmed bookings in zaca_events_table_booking_slot.
 *   - Any active meet at this location that overlaps the slot's time range —
 *     such a meet blocks all the ludoteca tables for that slot.
 *
 * The advance-days policy (non-Club vs Club) is applied here so the calendar
 * can display 'out_of_range' days without callers re-implementing it.
 */

namespace Zaca\Events\Service\Ludoteca;

use Magento\Framework\App\ResourceConnection;
use Zaca\Events\Helper\Data as Helper;

class AvailabilityService
{
    public const STATE_PAST = 'past';
    public const STATE_OUT_OF_RANGE = 'out_of_range';   // bookable by Club, not by this user
    public const STATE_NOT_YET = 'not_yet';             // beyond Club horizon — nobody can book yet
    public const STATE_CLOSED = 'closed';
    public const STATE_FREE = 'free';
    public const STATE_PARTIAL = 'partial';
    public const STATE_BUSY = 'busy';

    private ResourceConnection $resource;
    private Helper $helper;

    public function __construct(ResourceConnection $resource, Helper $helper)
    {
        $this->resource = $resource;
        $this->helper = $helper;
    }

    /**
     * State for every day of the requested month.
     *
     * @return array<string, string> ['Y-m-d' => state]
     */
    public function monthCalendar(int $locationId, int $year, int $month, ?int $customerId): array
    {
        $today = new \DateTimeImmutable('today');
        $maxAdvanceUser = $this->helper->getMaxAdvanceDays($customerId);
        $maxAdvanceClub = $this->helper->getClubMaxAdvanceDays();
        $userBookableDate = $today->modify('+' . max(0, $maxAdvanceUser - 1) . ' days');
        $clubBookableDate = $today->modify('+' . max(0, $maxAdvanceClub - 1) . ' days');

        $monthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $monthEnd = $monthStart->modify('last day of this month');

        $totalTables = $this->getTotalTables($locationId);
        $slotsByDow = $this->loadActiveTimeSlots($locationId);
        $ludOccupancy = $this->fetchLudotecaOccupancy($locationId, $monthStart, $monthEnd);
        $meetSegments = $this->fetchMeetSegments($locationId, $monthStart, $monthEnd);

        $result = [];
        $period = new \DatePeriod(
            $monthStart,
            new \DateInterval('P1D'),
            $monthEnd->modify('+1 day')
        );

        foreach ($period as $day) {
            /** @var \DateTimeImmutable $day */
            $dateKey = $day->format('Y-m-d');

            if ($day < $today) {
                $result[$dateKey] = self::STATE_PAST;
                continue;
            }
            // Past the Club horizon → nobody can book yet.
            if ($day > $clubBookableDate) {
                $result[$dateKey] = self::STATE_NOT_YET;
                continue;
            }
            // Within Club horizon but past this user's horizon → only Club.
            if ($day > $userBookableDate) {
                $result[$dateKey] = self::STATE_OUT_OF_RANGE;
                continue;
            }
            if ($totalTables <= 0) {
                $result[$dateKey] = self::STATE_CLOSED;
                continue;
            }

            $slotsForDay = $this->slotsForDay($slotsByDow, (int) $day->format('N'));
            if (empty($slotsForDay)) {
                $result[$dateKey] = self::STATE_CLOSED;
                continue;
            }

            $perSlotStates = [];
            foreach ($slotsForDay as $slot) {
                $bookedLud = (int) ($ludOccupancy[$dateKey][$slot['time_slot_id']] ?? 0);
                $hasMeet = $this->hasMeetOverlapping(
                    $meetSegments,
                    $day,
                    $slot['start_time'],
                    $slot['end_time']
                );
                $free = $hasMeet ? 0 : ($totalTables - $bookedLud);
                $perSlotStates[] = $this->slotState($free, $totalTables);
            }
            $result[$dateKey] = $this->collapseDayStates($perSlotStates);
        }

        return $result;
    }

    /**
     * Detailed availability for a single date — for the slot-picker UI.
     *
     * Each row's `is_mine` flag is true when the given customer already has a
     * confirmed booking covering that slot. The UI uses it to render those
     * slots as "Reservado por ti" instead of "Sin disponibilidad".
     *
     * @return array<int, array{
     *     time_slot_id:int, start_time:string, end_time:string,
     *     free_tables:int, total_tables:int, state:string,
     *     is_mine:bool, mine_tables:int
     * }>
     */
    public function availabilityForDate(int $locationId, \DateTimeImmutable $date, ?int $customerId): array
    {
        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            return [];
        }
        $maxAdvance = $this->helper->getMaxAdvanceDays($customerId);
        $maxBookableDate = $today->modify('+' . max(0, $maxAdvance - 1) . ' days');
        if ($date > $maxBookableDate) {
            return [];
        }

        $totalTables = $this->getTotalTables($locationId);
        if ($totalTables <= 0) {
            return [];
        }

        $slotsByDow = $this->loadActiveTimeSlots($locationId);
        $slotsForDay = $this->slotsForDay($slotsByDow, (int) $date->format('N'));
        if (empty($slotsForDay)) {
            return [];
        }

        $monthStart = $date->modify('first day of this month');
        $monthEnd = $date->modify('last day of this month');
        $ludOccupancy = $this->fetchLudotecaOccupancy($locationId, $monthStart, $monthEnd);
        $meetSegments = $this->fetchMeetSegments($locationId, $monthStart, $monthEnd);
        $myBookings = $customerId !== null
            ? $this->fetchCustomerBookings($locationId, $customerId, $date, $date)
            : [];
        $dateKey = $date->format('Y-m-d');

        $rows = [];
        foreach ($slotsForDay as $slot) {
            $bookedLud = (int) ($ludOccupancy[$dateKey][$slot['time_slot_id']] ?? 0);
            $hasMeet = $this->hasMeetOverlapping(
                $meetSegments,
                $date,
                $slot['start_time'],
                $slot['end_time']
            );
            $free = $hasMeet ? 0 : max(0, $totalTables - $bookedLud);
            $mineTables = (int) ($myBookings[$dateKey][$slot['time_slot_id']] ?? 0);
            $rows[] = [
                'time_slot_id' => (int) $slot['time_slot_id'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'free_tables' => $free,
                'total_tables' => $totalTables,
                'state' => $this->slotState($free, $totalTables),
                'is_mine' => $mineTables > 0,
                'mine_tables' => $mineTables,
            ];
        }
        return $rows;
    }

    /**
     * How many tables are free in a single slot — for the booking validator.
     */
    public function freeTablesFor(int $locationId, \DateTimeImmutable $date, int $timeSlotId): int
    {
        $totalTables = $this->getTotalTables($locationId);
        if ($totalTables <= 0) {
            return 0;
        }

        $monthStart = $date->modify('first day of this month');
        $monthEnd = $date->modify('last day of this month');
        $ludOccupancy = $this->fetchLudotecaOccupancy($locationId, $monthStart, $monthEnd);
        $meetSegments = $this->fetchMeetSegments($locationId, $monthStart, $monthEnd);

        $slotInfo = $this->fetchTimeSlot($timeSlotId);
        if ($slotInfo === null || (int) $slotInfo['location_id'] !== $locationId) {
            return 0;
        }

        $dateKey = $date->format('Y-m-d');
        if ($this->hasMeetOverlapping($meetSegments, $date, $slotInfo['start_time'], $slotInfo['end_time'])) {
            return 0;
        }
        $bookedLud = (int) ($ludOccupancy[$dateKey][$timeSlotId] ?? 0);
        return max(0, $totalTables - $bookedLud);
    }

    private function getTotalTables(int $locationId): int
    {
        $connection = $this->resource->getConnection();
        $value = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('zaca_events_location'), ['total_tables'])
                ->where('location_id = ?', $locationId)
                ->where('is_active = ?', 1)
        );
        return $value === false ? 0 : (int) $value;
    }

    /**
     * @return array<int|string, array<int, array{time_slot_id:int, start_time:string, end_time:string, sort_order:int}>>
     *   Keys: 'all' (day_of_week NULL), 'weekday' (day_of_week=8), and 1..7 (specific ISO day).
     */
    private function loadActiveTimeSlots(int $locationId): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_time_slot'),
                    ['time_slot_id', 'day_of_week', 'start_time', 'end_time', 'sort_order']
                )
                ->where('location_id = ?', $locationId)
                ->where('is_active = ?', 1)
                ->order(['sort_order ASC', 'start_time ASC'])
        );

        $grouped = ['all' => [], 'weekday' => []];
        foreach ($rows as $row) {
            if ($row['day_of_week'] === null) {
                $key = 'all';
            } elseif ((int) $row['day_of_week'] === \Zaca\Events\Model\Config\Source\DayOfWeek::WEEKDAY) {
                $key = 'weekday';
            } else {
                $key = (int) $row['day_of_week'];
            }
            $grouped[$key][] = [
                'time_slot_id' => (int) $row['time_slot_id'],
                'start_time' => (string) $row['start_time'],
                'end_time' => (string) $row['end_time'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }
        return $grouped;
    }

    /**
     * @param array<int|string, array<int, array{time_slot_id:int, start_time:string, end_time:string, sort_order:int}>> $byDow
     * @return array<int, array{time_slot_id:int, start_time:string, end_time:string, sort_order:int}>
     */
    private function slotsForDay(array $byDow, int $isoDayOfWeek): array
    {
        $merged = $byDow['all'] ?? [];
        if ($isoDayOfWeek >= 1 && $isoDayOfWeek <= 5) {
            $merged = array_merge($merged, $byDow['weekday'] ?? []);
        }
        $merged = array_merge($merged, $byDow[$isoDayOfWeek] ?? []);
        usort($merged, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order']
                ?: strcmp($a['start_time'], $b['start_time']);
        });
        return $merged;
    }

    /**
     * @return array<string, array<int, int>>  ['Y-m-d' => [time_slot_id => tables_count]]
     */
    private function fetchLudotecaOccupancy(
        int $locationId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $connection = $this->resource->getConnection();
        $bookingSlot = $this->resource->getTableName('zaca_events_table_booking_slot');
        $booking = $this->resource->getTableName('zaca_events_table_booking');

        $select = $connection->select()
            ->from(['s' => $bookingSlot], ['booking_date', 'time_slot_id'])
            ->joinInner(['b' => $booking], 'b.booking_id = s.booking_id', [])
            ->columns(['tables_count' => new \Zend_Db_Expr('SUM(s.tables_count)')])
            ->where('s.location_id = ?', $locationId)
            ->where('s.booking_date >= ?', $from->format('Y-m-d'))
            ->where('s.booking_date <= ?', $to->format('Y-m-d'))
            ->where('b.status = ?', 'confirmed')
            ->group(['s.booking_date', 's.time_slot_id']);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $date = (string) $row['booking_date'];
            $slot = (int) $row['time_slot_id'];
            $result[$date][$slot] = (int) $row['tables_count'];
        }
        return $result;
    }

    /**
     * Confirmed bookings for the given customer at this location within the
     * date range, aggregated as ['Y-m-d' => [time_slot_id => tables_count]].
     *
     * @return array<string, array<int, int>>
     */
    private function fetchCustomerBookings(
        int $locationId,
        int $customerId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $connection = $this->resource->getConnection();
        $bookingSlot = $this->resource->getTableName('zaca_events_table_booking_slot');
        $booking = $this->resource->getTableName('zaca_events_table_booking');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['s' => $bookingSlot], ['booking_date', 'time_slot_id', 'tables_count'])
                ->joinInner(['b' => $booking], 'b.booking_id = s.booking_id', [])
                ->where('s.location_id = ?', $locationId)
                ->where('s.booking_date >= ?', $from->format('Y-m-d'))
                ->where('s.booking_date <= ?', $to->format('Y-m-d'))
                ->where('b.customer_id = ?', $customerId)
                ->where('b.status = ?', 'confirmed')
        );

        $result = [];
        foreach ($rows as $row) {
            $date = (string) $row['booking_date'];
            $slot = (int) $row['time_slot_id'];
            $result[$date][$slot] = ($result[$date][$slot] ?? 0) + (int) $row['tables_count'];
        }
        return $result;
    }

    /**
     * Active meets at this location that may overlap any slot in the requested
     * month. Any meet at the location occupies all the ludoteca tables during
     * its time range — we keep the segment list small by filtering loosely by
     * start_date and refining the overlap test in PHP.
     *
     * @return array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    private function fetchMeetSegments(
        int $locationId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $connection = $this->resource->getConnection();
        $windowStart = $from->modify('-1 day')->format('Y-m-d 00:00:00');
        $windowEnd = $to->modify('+1 day')->format('Y-m-d 23:59:59');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_meet'),
                    ['start_date', 'duration_minutes']
                )
                ->where('location_id = ?', $locationId)
                ->where('is_active = ?', 1)
                ->where('start_date <= ?', $windowEnd)
                ->where('start_date >= ?', $windowStart)
        );

        $segments = [];
        foreach ($rows as $row) {
            $start = new \DateTimeImmutable((string) $row['start_date']);
            $end = $start->modify('+' . (int) $row['duration_minutes'] . ' minutes');
            $segments[] = ['start' => $start, 'end' => $end];
        }
        return $segments;
    }

    /**
     * @param array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $segments
     */
    private function hasMeetOverlapping(
        array $segments,
        \DateTimeImmutable $date,
        string $slotStartTime,
        string $slotEndTime
    ): bool {
        if (empty($segments)) {
            return false;
        }
        $slotStart = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $slotStartTime);
        $slotEnd = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $slotEndTime);

        foreach ($segments as $seg) {
            if ($seg['start'] < $slotEnd && $seg['end'] > $slotStart) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array{time_slot_id:int, location_id:int, start_time:string, end_time:string}|null
     */
    private function fetchTimeSlot(int $timeSlotId): ?array
    {
        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_time_slot'),
                    ['time_slot_id', 'location_id', 'start_time', 'end_time']
                )
                ->where('time_slot_id = ?', $timeSlotId)
                ->where('is_active = ?', 1)
        );
        if (!$row) {
            return null;
        }
        return [
            'time_slot_id' => (int) $row['time_slot_id'],
            'location_id' => (int) $row['location_id'],
            'start_time' => (string) $row['start_time'],
            'end_time' => (string) $row['end_time'],
        ];
    }

    private function slotState(int $free, int $total): string
    {
        if ($free <= 0) {
            return self::STATE_BUSY;
        }
        if ($free >= $total) {
            return self::STATE_FREE;
        }
        return self::STATE_PARTIAL;
    }

    /**
     * @param array<int, string> $states
     */
    private function collapseDayStates(array $states): string
    {
        if (empty($states)) {
            return self::STATE_CLOSED;
        }
        $allBusy = true;
        $allFree = true;
        foreach ($states as $state) {
            if ($state !== self::STATE_BUSY) {
                $allBusy = false;
            }
            if ($state !== self::STATE_FREE) {
                $allFree = false;
            }
        }
        if ($allBusy) {
            return self::STATE_BUSY;
        }
        if ($allFree) {
            return self::STATE_FREE;
        }
        return self::STATE_PARTIAL;
    }
}
