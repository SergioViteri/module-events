<?php
/**
 * Returns the current customer's active bookings at a given location, as JSON.
 *
 * Used by the store page to refresh the "Tus próximas reservas" panel without
 * a full page reload after a successful reserve/cancel.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;

class MyBookings extends Action implements HttpGetActionInterface
{
    private CustomerSession $customerSession;
    private JsonFactory $jsonFactory;
    private TableBookingRepositoryInterface $bookings;
    private TimezoneInterface $timezone;
    private EventsHelper $helper;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        JsonFactory $jsonFactory,
        TableBookingRepositoryInterface $bookings,
        TimezoneInterface $timezone,
        EventsHelper $helper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->jsonFactory = $jsonFactory;
        $this->bookings = $bookings;
        $this->timezone = $timezone;
        $this->helper = $helper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->helper->isLudotecaEnabled()) {
            return $result->setHttpResponseCode(404)->setData(['ok' => false, 'error' => 'disabled']);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['ok' => true, 'items' => []]);
        }
        $locationId = (int) $this->getRequest()->getParam('location_id');
        if ($locationId <= 0) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_params']);
        }

        $customerId = (int) $this->customerSession->getCustomerId();
        $today = $this->timezone->date()->format('Y-m-d');

        $collection = $this->bookings->getByCustomer($customerId);
        $collection->addFieldToFilter('status', TableBookingInterface::STATUS_CONFIRMED);
        $collection->addFieldToFilter('booking_date', ['gteq' => $today]);
        $collection->addFieldToFilter('location_id', $locationId);
        $collection->setOrder('booking_date', 'ASC');

        $items = [];
        foreach ($collection as $booking) {
            $bookingId = (int) $booking->getBookingId();
            $items[] = [
                'booking_id' => $bookingId,
                'booking_date' => (string) $booking->getBookingDate(),
                'booking_date_human' => $this->formatDateHuman((string) $booking->getBookingDate()),
                'slots' => $this->fetchSlots($bookingId),
            ];
        }

        return $result->setData(['ok' => true, 'items' => $items]);
    }

    private function fetchSlots(int $bookingId): array
    {
        $rows = [];
        foreach ($this->bookings->getSlots($bookingId) as $slot) {
            $rows[] = [
                'start' => substr((string) $slot->getData('start_time'), 0, 5),
                'end' => substr((string) $slot->getData('end_time'), 0, 5),
                'tables' => (int) $slot->getTablesCount(),
            ];
        }
        return $rows;
    }

    private function formatDateHuman(string $ymd): string
    {
        try {
            return (new \DateTimeImmutable($ymd))->format('d/m/Y');
        } catch (\Throwable $e) {
            return $ymd;
        }
    }
}
