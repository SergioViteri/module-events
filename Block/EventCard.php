<?php
/**
 * Zacatrus Events Event Card Block
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block;

use Zaca\Events\Api\Data\EventInterface;
use Zaca\Events\Api\StoreRepositoryInterface;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EventCard extends Template
{
    /**
     * @var EventInterface
     */
    protected $event;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param StoreRepositoryInterface $storeRepository
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreRepositoryInterface $storeRepository,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeRepository = $storeRepository;
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Set event
     *
     * @param EventInterface $event
     * @return $this
     */
    public function setEvent(EventInterface $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get event
     *
     * @return EventInterface|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Get store name
     *
     * @return string
     */
    public function getStoreName()
    {
        if (!$this->event) {
            return '';
        }

        try {
            $store = $this->storeRepository->getById($this->event->getStoreId());
            return $store->getName() . ' - ' . $store->getCity();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format event date
     *
     * @param string $date
     * @return string
     */
    public function formatEventDate($date)
    {
        return date('d/m/Y H:i', strtotime($date));
    }

    /**
     * Get event type label
     *
     * @return string
     */
    public function getEventTypeLabel()
    {
        if (!$this->event) {
            return '';
        }

        $types = [
            'casual' => __('Casual'),
            'league' => __('Liga ligera'),
            'special' => __('Evento especial')
        ];

        return $types[$this->event->getEventType()] ?? $this->event->getEventType();
    }

    /**
     * Get available slots
     *
     * @return int
     */
    public function getAvailableSlots()
    {
        if (!$this->event) {
            return 0;
        }

        $collection = $this->registrationRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('event_id', $this->event->getEventId())
                ->addFilter('status', 'confirmed')
                ->create()
        );

        $confirmed = $collection->getTotalCount();
        return max(0, $this->event->getMaxSlots() - $confirmed);
    }

    /**
     * Check if customer is registered
     *
     * @return bool
     */
    public function isCustomerRegistered()
    {
        if (!$this->event || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->registrationRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('event_id', $this->event->getEventId())
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        return $collection->getTotalCount() > 0;
    }

    /**
     * Get registration status
     *
     * @return string|null
     */
    public function getRegistrationStatus()
    {
        if (!$this->isCustomerRegistered()) {
            return null;
        }

        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->registrationRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('event_id', $this->event->getEventId())
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        if ($collection->getTotalCount() > 0) {
            $items = $collection->getItems();
            return reset($items)->getStatus();
        }

        return null;
    }
}

