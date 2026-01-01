<?php
/**
 * Zacatrus Events Event List Block
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Block;

use Zacatrus\Events\Api\EventRepositoryInterface;
use Zacatrus\Events\Api\StoreRepositoryInterface;
use Zacatrus\Events\Api\RegistrationRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EventList extends Template
{
    /**
     * @var EventRepositoryInterface
     */
    protected $eventRepository;

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
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var array|null
     */
    protected $events = null;

    /**
     * @var array|null
     */
    protected $stores = null;

    /**
     * @param Context $context
     * @param EventRepositoryInterface $eventRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        EventRepositoryInterface $eventRepository,
        StoreRepositoryInterface $storeRepository,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->eventRepository = $eventRepository;
        $this->storeRepository = $storeRepository;
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Get events
     *
     * @return \Zacatrus\Events\Api\Data\EventInterface[]
     */
    public function getEvents()
    {
        if ($this->events === null) {
            $storeId = $this->getRequest()->getParam('store_id');
            $eventType = $this->getRequest()->getParam('event_type');

            $this->searchCriteriaBuilder->addFilter('is_active', 1);
            
            // Filter by store
            if ($storeId) {
                $this->searchCriteriaBuilder->addFilter('store_id', $storeId);
            }

            // Filter by event type
            if ($eventType) {
                $this->searchCriteriaBuilder->addFilter('event_type', $eventType);
            }

            // Only future events
            $now = new \DateTime();
            $this->searchCriteriaBuilder->addFilter('start_date', $now->format('Y-m-d H:i:s'), 'gteq');

            // Sort by start date ascending
            $sortOrder = $this->sortOrderBuilder
                ->setField('start_date')
                ->setDirection('ASC')
                ->create();
            $this->searchCriteriaBuilder->addSortOrder($sortOrder);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->eventRepository->getList($searchCriteria);
            $this->events = $searchResults->getItems();
        }

        return $this->events;
    }

    /**
     * Get stores
     *
     * @return \Zacatrus\Events\Api\Data\StoreInterface[]
     */
    public function getStores()
    {
        if ($this->stores === null) {
            $this->searchCriteriaBuilder->addFilter('is_active', 1);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->storeRepository->getList($searchCriteria);
            $this->stores = $searchResults->getItems();
        }

        return $this->stores;
    }

    /**
     * Get event types
     *
     * @return array
     */
    public function getEventTypes()
    {
        return [
            'casual' => __('Casual'),
            'league' => __('Liga ligera'),
            'special' => __('Evento especial')
        ];
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get registration status for event
     *
     * @param int $eventId
     * @return string|null
     */
    public function getRegistrationStatus($eventId)
    {
        if (!$this->isCustomerLoggedIn()) {
            return null;
        }

        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->registrationRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('event_id', $eventId)
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        if ($collection->getTotalCount() > 0) {
            $items = $collection->getItems();
            return reset($items)->getStatus();
        }

        return null;
    }

    /**
     * Get available slots for event
     *
     * @param \Zacatrus\Events\Api\Data\EventInterface $event
     * @return int
     */
    public function getAvailableSlots($event)
    {
        $collection = $this->registrationRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('event_id', $event->getEventId())
                ->addFilter('status', 'confirmed')
                ->create()
        );

        $confirmed = $collection->getTotalCount();
        return max(0, $event->getMaxSlots() - $confirmed);
    }

    /**
     * Get registration URL
     *
     * @param int $eventId
     * @return string
     */
    public function getRegisterUrl($eventId)
    {
        return $this->getUrl('events/index/register', ['eventId' => $eventId]);
    }

    /**
     * Get unregister URL
     *
     * @param int $eventId
     * @return string
     */
    public function getUnregisterUrl($eventId)
    {
        return $this->getUrl('events/index/unregister', ['eventId' => $eventId]);
    }
}

