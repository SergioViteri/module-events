<?php

namespace Zaca\Events\Block\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;

class Landing extends Template
{
    public const CAPACITY_PER_TABLE = 6;

    private LocationCollectionFactory $locationCollectionFactory;
    private EventsHelper $helper;
    private ResourceConnection $resource;
    private CustomerSession $customerSession;
    private TableBookingRepositoryInterface $bookings;
    private TimezoneInterface $timezone;

    /** @var array<int, int>|null */
    private ?array $slotCountCache = null;

    /** @var array<int, array{id:int,name:string}>|null */
    private ?array $locationNameCache = null;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $myActiveBookingsCache = null;

    public function __construct(
        Context $context,
        LocationCollectionFactory $locationCollectionFactory,
        EventsHelper $helper,
        ResourceConnection $resource,
        CustomerSession $customerSession,
        TableBookingRepositoryInterface $bookings,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->helper = $helper;
        $this->resource = $resource;
        $this->customerSession = $customerSession;
        $this->bookings = $bookings;
        $this->timezone = $timezone;
    }

    /**
     * @return \Zaca\Events\Model\Location[]
     */
    public function getLocations(): array
    {
        $collection = $this->locationCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('total_tables', ['gt' => 0]);
        $collection->setOrder('name', 'ASC');
        return $collection->getItems();
    }

    public function getStoreUrl(string $slug): string
    {
        return $this->helper->getLudotecaPublicUrl($slug);
    }

    /**
     * Active time-slot count per location, fetched once.
     */
    public function getSlotCountFor(int $locationId): int
    {
        if ($this->slotCountCache === null) {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('zaca_events_time_slot'),
                        ['location_id', 'count' => new \Zend_Db_Expr('COUNT(*)')]
                    )
                    ->where('is_active = ?', 1)
                    ->group('location_id')
            );
            $this->slotCountCache = [];
            foreach ($rows as $row) {
                $this->slotCountCache[(int) $row['location_id']] = (int) $row['count'];
            }
        }
        return $this->slotCountCache[$locationId] ?? 0;
    }

    public function getCapacityPerTable(): int
    {
        return self::CAPACITY_PER_TABLE;
    }

    /**
     * Build a single-line address string for display.
     */
    public function formatAddress(\Zaca\Events\Model\Location $location): string
    {
        $parts = array_filter([
            trim((string) $location->getAddress()),
            trim((string) $location->getPostalCode()),
            trim((string) $location->getCity()),
        ], static fn ($v) => $v !== '');
        return implode(', ', $parts);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getCancelMyBookingUrl(): string
    {
        return $this->helper->getLudotecaPublicUrl('cancelmine');
    }

    /**
     * Confirmed bookings of the logged-in customer with booking_date >= today,
     * each with its slots and location info pre-loaded.
     *
     * @return array<int, array{
     *     booking_id:int,
     *     booking_date:string,
     *     location_name:string,
     *     slots:array<int, array{start:string, end:string, tables:int}>
     * }>
     */
    public function getMyActiveBookings(): array
    {
        if ($this->myActiveBookingsCache !== null) {
            return $this->myActiveBookingsCache;
        }
        if (!$this->customerSession->isLoggedIn()) {
            return $this->myActiveBookingsCache = [];
        }
        $customerId = (int) $this->customerSession->getCustomerId();
        $today = $this->timezone->date()->format('Y-m-d');

        $collection = $this->bookings->getByCustomer($customerId);
        $collection->addFieldToFilter('status', TableBookingInterface::STATUS_CONFIRMED);
        $collection->addFieldToFilter('booking_date', ['gteq' => $today]);
        $collection->setOrder('booking_date', 'ASC');

        $items = [];
        foreach ($collection as $booking) {
            $bookingId = (int) $booking->getBookingId();
            $items[] = [
                'booking_id' => $bookingId,
                'booking_date' => (string) $booking->getBookingDate(),
                'location_name' => $this->getLocationName((int) $booking->getLocationId()),
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

    private function getLocationName(int $locationId): string
    {
        if ($this->locationNameCache === null) {
            $this->locationNameCache = [];
            $collection = $this->locationCollectionFactory->create();
            foreach ($collection as $loc) {
                $this->locationNameCache[(int) $loc->getId()] = (string) $loc->getName();
            }
        }
        return $this->locationNameCache[$locationId] ?? '';
    }

    public function formatBookingDate(string $ymd): string
    {
        try {
            return (new \DateTimeImmutable($ymd))->format('d/m/Y');
        } catch (\Throwable $e) {
            return $ymd;
        }
    }
}
