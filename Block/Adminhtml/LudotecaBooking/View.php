<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaBooking;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Controller\Adminhtml\LudotecaBooking\View as ViewController;
use Zaca\Events\Model\Ludoteca\TableBooking;
use Zaca\Events\Model\LocationFactory;

class View extends Template
{
    private Registry $registry;
    private TableBookingRepositoryInterface $bookings;
    private LocationFactory $locationFactory;
    private CustomerRepositoryInterface $customerRepo;

    public function __construct(
        Context $context,
        Registry $registry,
        TableBookingRepositoryInterface $bookings,
        LocationFactory $locationFactory,
        CustomerRepositoryInterface $customerRepo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->bookings = $bookings;
        $this->locationFactory = $locationFactory;
        $this->customerRepo = $customerRepo;
    }

    public function getBooking(): ?TableBooking
    {
        $booking = $this->registry->registry(ViewController::REGISTRY_KEY);
        return $booking instanceof TableBooking ? $booking : null;
    }

    public function getLocationName(): string
    {
        $b = $this->getBooking();
        if (!$b) {
            return '';
        }
        $loc = $this->locationFactory->create()->load($b->getLocationId());
        return $loc->getId() ? (string) $loc->getName() : '';
    }

    public function getCustomerLine(): string
    {
        $b = $this->getBooking();
        if (!$b) {
            return '';
        }
        try {
            $c = $this->customerRepo->getById($b->getCustomerId());
            $name = trim($c->getFirstname() . ' ' . $c->getLastname());
            return $name !== '' ? $name . ' <' . $c->getEmail() . '>' : (string) $c->getEmail();
        } catch (NoSuchEntityException $e) {
            return (string) __('Customer #%1 (deleted)', $b->getCustomerId());
        }
    }

    /**
     * @return array<int, array{start_time:string, end_time:string, tables_count:int}>
     */
    public function getSlots(): array
    {
        $b = $this->getBooking();
        if (!$b || !$b->getBookingId()) {
            return [];
        }
        $rows = [];
        foreach ($this->bookings->getSlots($b->getBookingId()) as $slot) {
            $rows[] = [
                'start_time' => substr((string) $slot->getData('start_time'), 0, 5),
                'end_time' => substr((string) $slot->getData('end_time'), 0, 5),
                'tables_count' => (int) $slot->getTablesCount(),
            ];
        }
        return $rows;
    }

    public function getCancelUrl(): string
    {
        $b = $this->getBooking();
        return $this->getUrl('zaca_events/ludotecabooking/cancel', [
            'booking_id' => $b ? $b->getBookingId() : 0,
        ]);
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('zaca_events/ludotecabooking/index');
    }
}
