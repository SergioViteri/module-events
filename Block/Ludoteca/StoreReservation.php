<?php

namespace Zaca\Events\Block\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\Location;

class StoreReservation extends Template
{
    private Registry $registry;
    private EventsHelper $helper;
    private CustomerSession $customerSession;
    private ResourceConnection $resource;
    private TableBookingRepositoryInterface $bookings;
    private TimezoneInterface $timezone;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $myActiveBookingsCache = null;

    public function __construct(
        Context $context,
        Registry $registry,
        EventsHelper $helper,
        CustomerSession $customerSession,
        ResourceConnection $resource,
        TableBookingRepositoryInterface $bookings,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->resource = $resource;
        $this->bookings = $bookings;
        $this->timezone = $timezone;
    }

    public function getLocation(): ?Location
    {
        $location = $this->registry->registry('current_ludoteca_location');
        return $location instanceof Location ? $location : null;
    }

    public function getCalendarUrl(): string
    {
        return $this->getUrl(
            $this->helper->getLudotecaRoutePath() . '/calendar',
            ['_secure' => true]
        );
    }

    public function getSlotsUrl(): string
    {
        return $this->getUrl(
            $this->helper->getLudotecaRoutePath() . '/slots',
            ['_secure' => true]
        );
    }

    public function getReserveUrl(): string
    {
        return $this->getUrl(
            $this->helper->getLudotecaRoutePath() . '/reserve',
            ['_secure' => true]
        );
    }

    public function getLandingUrl(): string
    {
        return $this->getUrl($this->helper->getLudotecaRoutePath());
    }

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login', [
            'referer' => base64_encode($this->getUrl('*/*/*', ['_current' => true])),
        ]);
    }

    public function getClubSignupUrl(): string
    {
        return $this->helper->getClubSignupUrl();
    }

    public function isClubMember(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return $this->helper->isClubMember((int) $this->customerSession->getCustomerId());
    }

    /**
     * True when the logged-in customer already has a confirmed booking with a
     * future (or today's) date. Used to inform non-Club users that they cannot
     * create a second reservation until they cancel the existing one.
     */
    public function hasActiveBooking(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $connection = $this->resource->getConnection();
        $count = (int) $connection->fetchOne(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_table_booking'),
                    ['count' => new \Zend_Db_Expr('COUNT(*)')]
                )
                ->where('customer_id = ?', (int) $this->customerSession->getCustomerId())
                ->where('status = ?', 'confirmed')
                ->where('booking_date >= ?', $this->timezone->date()->format('Y-m-d'))
        );
        return $count > 0;
    }

    public function getCancelMyBookingUrl(): string
    {
        return $this->helper->getLudotecaPublicUrl('cancelmine');
    }

    public function getMyBookingsUrl(): string
    {
        return $this->helper->getLudotecaPublicUrl('mybookings');
    }

    /**
     * Confirmed bookings of the logged-in customer at this store with
     * booking_date >= today, each with its slots.
     *
     * @return array<int, array{
     *     booking_id:int,
     *     booking_date:string,
     *     slots:array<int, array{start:string, end:string, tables:int}>
     * }>
     */
    public function getMyActiveBookingsAtLocation(): array
    {
        if ($this->myActiveBookingsCache !== null) {
            return $this->myActiveBookingsCache;
        }
        if (!$this->isLoggedIn()) {
            return $this->myActiveBookingsCache = [];
        }
        $location = $this->getLocation();
        if (!$location) {
            return $this->myActiveBookingsCache = [];
        }
        $customerId = (int) $this->customerSession->getCustomerId();
        $today = $this->timezone->date()->format('Y-m-d');

        $collection = $this->bookings->getByCustomer($customerId);
        $collection->addFieldToFilter('status', TableBookingInterface::STATUS_CONFIRMED);
        $collection->addFieldToFilter('booking_date', ['gteq' => $today]);
        $collection->addFieldToFilter('location_id', (int) $location->getId());
        $collection->setOrder('booking_date', 'ASC');

        $items = [];
        foreach ($collection as $booking) {
            $bookingId = (int) $booking->getBookingId();
            $items[] = [
                'booking_id' => $bookingId,
                'booking_date' => (string) $booking->getBookingDate(),
                'slots' => $this->fetchSlots($bookingId),
            ];
        }
        return $this->myActiveBookingsCache = $items;
    }

    /**
     * @return array<int, array{start:string, end:string, tables:int}>
     */
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

    public function formatBookingDate(string $ymd): string
    {
        try {
            return (new \DateTimeImmutable($ymd))->format('d/m/Y');
        } catch (\Throwable $e) {
            return $ymd;
        }
    }

    /**
     * Prefer the phone from the customer's most recent ludoteca booking; fall
     * back to the default billing address phone if none.
     */
    public function getCustomerPhone(): string
    {
        if (!$this->isLoggedIn()) {
            return '';
        }
        $customerId = (int) $this->customerSession->getCustomerId();

        $connection = $this->resource->getConnection();
        $previous = (string) $connection->fetchOne(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_table_booking'),
                    ['phone_number']
                )
                ->where('customer_id = ?', $customerId)
                ->where('phone_number != ?', '')
                ->order('booking_id DESC')
                ->limit(1)
        );
        if ($previous !== '') {
            return $previous;
        }

        $customer = $this->customerSession->getCustomer();
        $address = $customer->getDefaultBillingAddress();
        if ($address && $address->getTelephone()) {
            return (string) $address->getTelephone();
        }
        return '';
    }
}
