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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zaca\Events\Helper\Data as EventsHelper;

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
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array|null
     */
    protected $themes = null;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

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
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param EventsHelper $eventsHelper
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
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        EventsHelper $eventsHelper,
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
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->eventsHelper = $eventsHelper;
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
                
                // Event stays in the list until occurrence end (start + duration). After that it is hidden.
                // - Non-recurring: include while now < start_date + duration_minutes
                // - Recurring: include while now < next_occurrence + duration_minutes (and next occurrence <= end_date if set)
                $store = $this->storeManager->getStore();
                $timezoneCode = $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $store->getCode());
                $timezoneObj = new \DateTimeZone($timezoneCode);
                $now = new \DateTime('now', $timezoneObj);
                
                $filteredEvents = [];
                foreach ($allEvents as $event) {
                    $recurrenceType = $event->getRecurrenceType();
                    $durationMinutes = (int) $event->getDurationMinutes();
                    
                    if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_NONE) {
                        $start = new \DateTime($event->getStartDate(), new \DateTimeZone('UTC'));
                        $start->setTimezone($timezoneObj);
                        $occurrenceEnd = clone $start;
                        $occurrenceEnd->modify('+' . $durationMinutes . ' minutes');
                        if ($now < $occurrenceEnd) {
                            $filteredEvents[] = $event;
                        }
                    } else {
                        $nextOccurrence = $this->getNextOccurrenceDate($event);
                        
                        if ($nextOccurrence === null) {
                            continue;
                        }
                        
                        $endDate = $event->getEndDate();
                        if ($endDate !== null) {
                            $endDateTime = new \DateTime($endDate, new \DateTimeZone('UTC'));
                            $nextOccurrenceDateTime = new \DateTime($nextOccurrence, new \DateTimeZone('UTC'));
                            if ($nextOccurrenceDateTime > $endDateTime) {
                                continue;
                            }
                        }
                        
                        $occurrenceEnd = new \DateTime($nextOccurrence, new \DateTimeZone('UTC'));
                        $occurrenceEnd->setTimezone($timezoneObj);
                        $occurrenceEnd->modify('+' . $durationMinutes . ' minutes');
                        if ($now < $occurrenceEnd) {
                            $filteredEvents[] = $event;
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
        $confirmed = $this->registrationRepository->getConfirmedAttendeeCountForMeet($meet->getMeetId());
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

        // Convert UTC dates to store timezone for calculations
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        $startDate = new \DateTime($event->getStartDate(), new \DateTimeZone('UTC'));
        $startDate->setTimezone($timezoneObj);
        
        $now = new \DateTime('now', $timezoneObj);

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

        // Convert back to UTC for storage/formatting
        $nextDate->setTimezone(new \DateTimeZone('UTC'));
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

    /**
     * Get route path for events
     *
     * @return string
     */
    public function getRoutePath()
    {
        return $this->eventsHelper->getRoutePath();
    }
}

