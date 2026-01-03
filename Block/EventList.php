<?php
/**
 * Zacatrus Events Event List Block
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block;

use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\EventTypeRepositoryInterface;
use Zaca\Events\Api\ThemeRepositoryInterface;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class EventList extends Template
{
    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

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
    protected $locations = null;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EventTypeRepositoryInterface
     */
    protected $eventTypeRepository;

    /**
     * @var ThemeRepositoryInterface
     */
    protected $themeRepository;

    /**
     * @var array|null
     */
    protected $themes = null;

    /**
     * @param Context $context
     * @param MeetRepositoryInterface $meetRepository
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param LocationCollectionFactory $locationCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param EventTypeRepositoryInterface $eventTypeRepository
     * @param ThemeRepositoryInterface $themeRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        MeetRepositoryInterface $meetRepository,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder,
        LocationCollectionFactory $locationCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        EventTypeRepositoryInterface $eventTypeRepository,
        ThemeRepositoryInterface $themeRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->meetRepository = $meetRepository;
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->themeRepository = $themeRepository;
    }

    /**
     * Get meets
     *
     * @return \Zaca\Events\Api\Data\MeetInterface[]
     */
    public function getEvents()
    {
        if ($this->events === null) {
            try {
                $locationId = $this->getRequest()->getParam('location_id');
                $meetType = $this->getRequest()->getParam('meet_type');
                $themeId = $this->getRequest()->getParam('theme_id');

                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteriaBuilder->addFilter('is_active', 1);
                
                // Filter by location
                if ($locationId) {
                    $searchCriteriaBuilder->addFilter('location_id', $locationId);
                }

                // Filter by meet type
                if ($meetType) {
                    $searchCriteriaBuilder->addFilter('meet_type', $meetType);
                }

                // Filter by theme
                if ($themeId) {
                    $searchCriteriaBuilder->addFilter('theme_id', $themeId);
                }

                // Sort by start date ascending
                $sortOrder = $this->sortOrderBuilder
                    ->setField('start_date')
                    ->setDirection('ASC')
                    ->create();
                $searchCriteriaBuilder->addSortOrder($sortOrder);

                $searchCriteria = $searchCriteriaBuilder->create();
                $searchResults = $this->meetRepository->getList($searchCriteria);
                $allEvents = $searchResults->getItems();
                
                // Date filtering: 
                // - For non-recurring events: filter by start_date >= now
                // - For recurring events: allow past start dates, filter by end_date if exists
                $now = new \DateTime();
                $nowStr = $now->format('Y-m-d H:i:s');
                
                $filteredEvents = [];
                foreach ($allEvents as $event) {
                    $recurrenceType = $event->getRecurrenceType();
                    
                    if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_NONE) {
                        // Non-recurring: must have future start date
                        if ($event->getStartDate() >= $nowStr) {
                            $filteredEvents[] = $event;
                        }
                    } else {
                        // Recurring: allow past start dates, but check if next occurrence is before or equal to end_date
                        $nextOccurrence = $this->getNextOccurrenceDate($event);
                        
                        if ($nextOccurrence === null) {
                            // Could not calculate next occurrence, skip
                            continue;
                        }
                        
                        $endDate = $event->getEndDate();
                        if ($endDate === null) {
                            // No end date set, include event
                            $filteredEvents[] = $event;
                        } else {
                            // Compare full datetime (including time) - only include if next occurrence is before or equal to end date
                            $nextOccurrenceDateTime = new \DateTime($nextOccurrence);
                            $endDateTime = new \DateTime($endDate);
                            
                            if ($nextOccurrenceDateTime <= $endDateTime) {
                                // Next occurrence is before or equal to end date/time, include event
                                $filteredEvents[] = $event;
                            }
                            // If next occurrence is after end date/time, don't include
                        }
                    }
                }
                
                $this->events = $filteredEvents;
            } catch (\Exception $e) {
                // Return empty array on error
                $this->events = [];
            }
        }

        return $this->events;
    }

    /**
     * Get locations
     *
     * @return \Zaca\Events\Model\Location[]
     */
    public function getLocations()
    {
        if ($this->locations === null) {
            try {
                $locationCollection = $this->locationCollectionFactory->create();
                $locationCollection->addFieldToFilter('is_active', 1);
                $this->locations = $locationCollection->getItems();
            } catch (\Exception $e) {
                $this->locations = [];
            }
        }

        return $this->locations ?: [];
    }

    /**
     * Get meet types
     *
     * @return array
     */
    public function getEventTypes()
    {
        $eventTypes = [];
        $collection = $this->eventTypeRepository->getActiveEventTypes();
        foreach ($collection as $eventType) {
            $eventTypes[$eventType->getCode()] = __($eventType->getName());
        }
        return $eventTypes;
    }

    /**
     * Get themes
     *
     * @return \Zaca\Events\Api\Data\ThemeInterface[]
     */
    public function getThemes()
    {
        if ($this->themes === null) {
            try {
                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteriaBuilder->addFilter('is_active', 1);
                $sortOrder = $this->sortOrderBuilder
                    ->setField('sort_order')
                    ->setDirection('ASC')
                    ->create();
                $searchCriteriaBuilder->addSortOrder($sortOrder);
                $searchCriteria = $searchCriteriaBuilder->create();
                $searchResults = $this->themeRepository->getList($searchCriteria);
                $this->themes = $searchResults->getItems();
            } catch (\Exception $e) {
                $this->themes = [];
            }
        }

        return $this->themes ?: [];
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
     * Get registration status for meet
     *
     * @param int $meetId
     * @return string|null
     */
    public function getRegistrationStatus($meetId)
    {
        if (!$this->isCustomerLoggedIn()) {
            return null;
        }

        $customerId = $this->customerSession->getCustomerId();
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $meetId)
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
     * Get available slots for meet
     *
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @return int
     */
    public function getAvailableSlots($meet)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $meet->getMeetId())
                ->addFilter('status', 'confirmed')
                ->create()
        );

        $confirmed = $collection->getTotalCount();
        return max(0, $meet->getMaxSlots() - $confirmed);
    }

    /**
     * Get registration URL
     *
     * @param int $meetId
     * @return string
     */
    public function getRegisterUrl($meetId)
    {
        return $this->getUrl('events/index/register', ['meetId' => $meetId]);
    }

    /**
     * Get unregister URL
     *
     * @param int $meetId
     * @return string
     */
    public function getUnregisterUrl($meetId)
    {
        return $this->getUrl('events/index/unregister', ['meetId' => $meetId]);
    }

    /**
     * Get next occurrence date for recurring events
     *
     * @param MeetInterface $event
     * @return string|null
     */
    protected function getNextOccurrenceDate(MeetInterface $event)
    {
        $recurrenceType = $event->getRecurrenceType();
        
        if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_NONE) {
            return null;
        }

        $startDate = new \DateTime($event->getStartDate());
        $now = new \DateTime();

        // Calculate next occurrence
        $nextDate = clone $startDate;

        if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            // Biweekly (every 15 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+15 days');
            }
        } elseif ($recurrenceType === MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            // Weekly (every 7 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+7 days');
            }
        } else {
            return null;
        }

        return $nextDate->format('Y-m-d H:i:s');
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            'zaca_events/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }
}

