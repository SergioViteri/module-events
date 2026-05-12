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
     * Map of meet_id => RegistrationInterface for the current customer (lazy-loaded).
     * `false` while not loaded; an array (possibly empty) after the first lookup.
     *
     * @var array|false
     */
    protected $myRegistrationsMap = false;

    /**
     * Cached list of the current customer's confirmed meets that are still visible.
     *
     * @var MeetInterface[]|null
     */
    protected $myVisibleConfirmedEvents = null;

    /**
     * Cached list of all active meets whose occurrence has not finished yet
     * (independent of the request's filters). Used to build the filter chips.
     *
     * @var MeetInterface[]|null
     */
    protected $allVisibleEvents = null;

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
            $locationId = $this->getRequest()->getParam('location_id');
            $meetType = $this->getRequest()->getParam('meet_type');
            $themeId = $this->getRequest()->getParam('theme_id');

            $events = $this->getAllVisibleEvents();

            if ($locationId) {
                $events = array_filter($events, function ($event) use ($locationId) {
                    return (int) $event->getLocationId() === (int) $locationId;
                });
            }
            if ($meetType) {
                $events = array_filter($events, function ($event) use ($meetType) {
                    return $event->getMeetType() === $meetType;
                });
            }
            if ($themeId) {
                $events = array_filter($events, function ($event) use ($themeId) {
                    return (int) $event->getThemeId() === (int) $themeId;
                });
            }

            // Optional filter: only events the current customer is subscribed to (status=confirmed)
            if ($this->isMyEventsFilterActive() && $this->isCustomerLoggedIn()) {
                $confirmedMeetIds = $this->getConfirmedMeetIds();
                $events = array_filter($events, function ($event) use ($confirmedMeetIds) {
                    return in_array((int) $event->getMeetId(), $confirmedMeetIds, true);
                });
            }

            $this->events = array_values($events);
        }

        return $this->events;
    }

    /**
     * All active meets whose current/next occurrence has not finished yet.
     * Independent of the request's filters; source of truth for both grid and filter chips.
     *
     * @return MeetInterface[]
     */
    public function getAllVisibleEvents()
    {
        if ($this->allVisibleEvents === null) {
            try {
                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteriaBuilder->addFilter('is_active', 1);
                $sortOrder = $this->sortOrderBuilder
                    ->setField('start_date')
                    ->setDirection('ASC')
                    ->create();
                $searchCriteriaBuilder->addSortOrder($sortOrder);
                $searchCriteria = $searchCriteriaBuilder->create();
                $results = $this->meetRepository->getList($searchCriteria);
                $this->allVisibleEvents = $this->filterEventsByOccurrence($results->getItems());
            } catch (\Exception $e) {
                $this->allVisibleEvents = [];
            }
        }
        return $this->allVisibleEvents;
    }

    /**
     * Get map of meet_id => RegistrationInterface for the current customer (any status).
     * Cached per request. Lets EventCard avoid N+1 lookups.
     *
     * @return array<int, \Zaca\Events\Api\Data\RegistrationInterface>
     */
    public function getMyRegistrationsMap()
    {
        if ($this->myRegistrationsMap === false) {
            $this->myRegistrationsMap = [];
            if ($this->isCustomerLoggedIn()) {
                try {
                    $customerId = $this->customerSession->getCustomerId();
                    $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                    $results = $this->registrationRepository->getList(
                        $searchCriteriaBuilder
                            ->addFilter('customer_id', $customerId)
                            ->create()
                    );
                    foreach ($results->getItems() as $registration) {
                        $this->myRegistrationsMap[(int) $registration->getMeetId()] = $registration;
                    }
                } catch (\Exception $e) {
                    // Leave the map empty on error
                }
            }
        }
        return $this->myRegistrationsMap;
    }

    /**
     * Meet IDs where the current customer has a confirmed registration.
     *
     * @return int[]
     */
    public function getConfirmedMeetIds()
    {
        $meetIds = [];
        foreach ($this->getMyRegistrationsMap() as $meetId => $registration) {
            if ($registration->getStatus() === \Zaca\Events\Api\Data\RegistrationInterface::STATUS_CONFIRMED) {
                $meetIds[] = (int) $meetId;
            }
        }
        return $meetIds;
    }

    /**
     * Meets the current customer is confirmed for AND that are still visible
     * (active + occurrence not finished). Independent of the request's other filters.
     *
     * @return MeetInterface[]
     */
    public function getMyVisibleConfirmedEvents()
    {
        if ($this->myVisibleConfirmedEvents !== null) {
            return $this->myVisibleConfirmedEvents;
        }

        $confirmedIds = $this->getConfirmedMeetIds();
        if (empty($confirmedIds)) {
            return $this->myVisibleConfirmedEvents = [];
        }

        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter('is_active', 1);
            $searchCriteriaBuilder->addFilter('meet_id', $confirmedIds, 'in');
            $searchCriteria = $searchCriteriaBuilder->create();
            $results = $this->meetRepository->getList($searchCriteria);
            $this->myVisibleConfirmedEvents = $this->filterEventsByOccurrence($results->getItems());
        } catch (\Exception $e) {
            $this->myVisibleConfirmedEvents = [];
        }

        return $this->myVisibleConfirmedEvents;
    }

    /**
     * Count of the current customer's still-visible confirmed registrations.
     *
     * @return int
     */
    public function getMyEventsCount()
    {
        return count($this->getMyVisibleConfirmedEvents());
    }

    /**
     * Keep only events whose current/next occurrence has not finished yet.
     *
     * @param MeetInterface[] $events
     * @return MeetInterface[]
     */
    protected function filterEventsByOccurrence(array $events)
    {
        $store = $this->storeManager->getStore();
        $timezoneCode = $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $store->getCode());
        $timezoneObj = new \DateTimeZone($timezoneCode);
        $now = new \DateTime('now', $timezoneObj);

        $filtered = [];
        foreach ($events as $event) {
            $recurrenceType = $event->getRecurrenceType();
            $durationMinutes = (int) $event->getDurationMinutes();

            if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_NONE) {
                $start = new \DateTime($event->getStartDate(), new \DateTimeZone('UTC'));
                $start->setTimezone($timezoneObj);
                $occurrenceEnd = clone $start;
                $occurrenceEnd->modify('+' . $durationMinutes . ' minutes');
                if ($now < $occurrenceEnd) {
                    $filtered[] = $event;
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
                    $filtered[] = $event;
                }
            }
        }
        return $filtered;
    }

    /**
     * Whether the "my events" lens is active for the current request.
     *
     * @return bool
     */
    public function isMyEventsFilterActive()
    {
        return (bool) $this->getRequest()->getParam('my_events');
    }

    /**
     * Build the URL that toggles the "my events" lens, preserving the current query string.
     *
     * @return string
     */
    public function getMyEventsToggleUrl()
    {
        $params = $this->getRequest()->getParams();
        if ($this->isMyEventsFilterActive()) {
            unset($params['my_events']);
        } else {
            $params['my_events'] = 1;
        }
        return $this->getUrl($this->getRoutePath(), ['_query' => $params]);
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
                $visibleLocationIds = [];
                foreach ($this->getAllVisibleEvents() as $event) {
                    $visibleLocationIds[(int) $event->getLocationId()] = true;
                }
                if (empty($visibleLocationIds)) {
                    $this->locations = [];
                } else {
                    $locationCollection = $this->locationCollectionFactory->create();
                    $locationCollection->addFieldToFilter('is_active', 1);
                    $locationCollection->addFieldToFilter('location_id', ['in' => array_keys($visibleLocationIds)]);
                    $this->locations = $locationCollection->getItems();
                }
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
        $visibleCodes = [];
        foreach ($this->getAllVisibleEvents() as $event) {
            $code = $event->getMeetType();
            if ($code) {
                $visibleCodes[$code] = true;
            }
        }
        if (empty($visibleCodes)) {
            return [];
        }
        $eventTypes = [];
        foreach ($this->eventTypeRepository->getActiveEventTypes() as $eventType) {
            if (isset($visibleCodes[$eventType->getCode()])) {
                $eventTypes[$eventType->getCode()] = __($eventType->getName());
            }
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
                $visibleThemeIds = [];
                foreach ($this->getAllVisibleEvents() as $event) {
                    $themeId = $event->getThemeId();
                    if ($themeId) {
                        $visibleThemeIds[(int) $themeId] = true;
                    }
                }
                if (empty($visibleThemeIds)) {
                    $this->themes = [];
                } else {
                    $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                    $searchCriteriaBuilder->addFilter('is_active', 1);
                    $searchCriteriaBuilder->addFilter('theme_id', array_keys($visibleThemeIds), 'in');
                    $sortOrder = $this->sortOrderBuilder
                        ->setField('sort_order')
                        ->setDirection('ASC')
                        ->create();
                    $searchCriteriaBuilder->addSortOrder($sortOrder);
                    $searchCriteria = $searchCriteriaBuilder->create();
                    $searchResults = $this->themeRepository->getList($searchCriteria);
                    $this->themes = $searchResults->getItems();
                }
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

