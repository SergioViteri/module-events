<?php

namespace Zaca\Events\Block\Ludoteca;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Controller\Ludoteca\Attendance;
use Zaca\Events\Model\Ludoteca\TableBooking;
use Zaca\Events\Model\Location;

class AttendanceCheck extends Template
{
    private Registry $registry;
    private TableBookingRepositoryInterface $bookings;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        Registry $registry,
        TableBookingRepositoryInterface $bookings,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->bookings = $bookings;
        $this->logger = $logger;
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function getResult(): array
    {
        $result = $this->registry->registry(Attendance::REGISTRY_RESULT);
        if (!is_array($result)) {
            return ['ok' => false, 'message' => __('No se ha podido validar este QR.')];
        }
        return $result;
    }

    public function getBooking(): ?TableBooking
    {
        $booking = $this->registry->registry(Attendance::REGISTRY_BOOKING);
        return $booking instanceof TableBooking ? $booking : null;
    }

    public function getLocation(): ?Location
    {
        $location = $this->registry->registry(Attendance::REGISTRY_LOCATION);
        return $location instanceof Location ? $location : null;
    }

    /**
     * @return array<int, array{start_time:string, end_time:string, tables_count:int}>
     */
    public function getSlots(): array
    {
        $booking = $this->getBooking();
        if (!$booking || !$booking->getBookingId()) {
            return [];
        }
        try {
            $rows = [];
            foreach ($this->bookings->getSlots($booking->getBookingId()) as $slot) {
                $rows[] = [
                    'start_time' => (string) $slot->getData('start_time'),
                    'end_time' => (string) $slot->getData('end_time'),
                    'tables_count' => (int) $slot->getTablesCount(),
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca AttendanceBlock] ' . $e->getMessage());
            return [];
        }
    }
}
