<?php
/**
 * In-store check-in landing for a booking QR.
 *
 * Validates: booking exists, is confirmed, today is within booking_date, and
 * the location code from the URL matches the booking's location. Then renders
 * a Block that confirms the check-in.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\LocationFactory;

class Attendance extends Action
{
    public const REGISTRY_BOOKING = 'ludoteca_attendance_booking';
    public const REGISTRY_LOCATION = 'ludoteca_attendance_location';
    public const REGISTRY_RESULT = 'ludoteca_attendance_result';

    private PageFactory $pageFactory;
    private TableBookingRepositoryInterface $bookings;
    private LocationFactory $locationFactory;
    private EventsHelper $helper;
    private Registry $registry;
    private LoggerInterface $logger;
    private ResourceConnection $resource;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        TableBookingRepositoryInterface $bookings,
        LocationFactory $locationFactory,
        EventsHelper $helper,
        Registry $registry,
        LoggerInterface $logger,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->bookings = $bookings;
        $this->locationFactory = $locationFactory;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->resource = $resource;
    }

    public function execute()
    {
        $bookingId = (int) $this->getRequest()->getParam('id');
        $code = (string) $this->getRequest()->getParam('code');

        $resultStatus = ['ok' => false, 'message' => __('No se ha podido validar este QR.')];
        $bookingForRegistry = null;
        $locationForRegistry = null;

        if ($bookingId > 0 && $code !== '') {
            try {
                $booking = $this->bookings->getById($bookingId);
                $location = $this->locationFactory->create();
                $location->load($booking->getLocationId());

                $bookingForRegistry = $booking;
                $locationForRegistry = $location;

                if (!$location->getId() || $location->getCode() !== $code) {
                    $resultStatus = ['ok' => false, 'message' => __('Código de tienda no válido.')];
                } elseif ($booking->getStatus() !== TableBookingInterface::STATUS_CONFIRMED) {
                    $resultStatus = ['ok' => false, 'message' => __('Esta reserva no está confirmada.')];
                } elseif ($booking->getBookingDate() !== (new \DateTimeImmutable('today'))->format('Y-m-d')) {
                    $resultStatus = [
                        'ok' => false,
                        'message' => __('Esta reserva es para el día %1.', $booking->getBookingDate()),
                    ];
                } else {
                    $alreadyChecked = $this->recordAttendance(
                        (int) $booking->getBookingId(),
                        (int) $location->getId(),
                        $booking->getBookingDate()
                    );
                    $resultStatus = [
                        'ok' => true,
                        'message' => $alreadyChecked
                            ? __('Reserva ya registrada hoy. ¡Bienvenidos!')
                            : __('Reserva válida. ¡Bienvenidos a Zacatrus!'),
                    ];
                }
            } catch (NoSuchEntityException $e) {
                $resultStatus = ['ok' => false, 'message' => __('Reserva no encontrada.')];
            } catch (\Throwable $e) {
                $this->logger->error('[Ludoteca Attendance] ' . $e->getMessage());
            }
        }

        $this->registry->register(self::REGISTRY_BOOKING, $bookingForRegistry, true);
        $this->registry->register(self::REGISTRY_LOCATION, $locationForRegistry, true);
        $this->registry->register(self::REGISTRY_RESULT, $resultStatus, true);

        return $this->pageFactory->create();
    }

    /**
     * Insert one row in zaca_events_attendance for this booking-day. Returns
     * true if a row already existed (idempotent rescan), false if a new row
     * was created.
     */
    private function recordAttendance(int $bookingId, int $locationId, string $date): bool
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('zaca_events_attendance');
        try {
            $connection->insert($table, [
                'booking_id' => $bookingId,
                'registration_id' => null,
                'location_id' => $locationId,
                'attendance_date' => $date,
            ]);
            return false;
        } catch (\Magento\Framework\DB\Adapter\DuplicateException $e) {
            return true;
        } catch (\Zend_Db_Statement_Exception $e) {
            // MySQL duplicate-key returns errorInfo SQLSTATE 23000.
            $code = method_exists($e, 'getCode') ? (int) $e->getCode() : 0;
            if ($code === 23000) {
                return true;
            }
            $this->logger->error('[Ludoteca Attendance] DB error: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Attendance] Insert error: ' . $e->getMessage());
            return false;
        }
    }
}
