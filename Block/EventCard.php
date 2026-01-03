<?php
/**
 * Zacatrus Events Event Card Block
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block;

use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\EventTypeRepositoryInterface;
use Zaca\Events\Api\ThemeRepositoryInterface;
use Zaca\Events\Helper\Calendar;
use Zaca\Events\Api\Data\RegistrationInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class EventCard extends Template
{
    /**
     * @var MeetInterface
     */
    protected $event;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

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
     * @var EventTypeRepositoryInterface
     */
    protected $eventTypeRepository;

    /**
     * @var ThemeRepositoryInterface
     */
    protected $themeRepository;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var Calendar
     */
    protected $calendarHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param LocationFactory $locationFactory
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param EventTypeRepositoryInterface $eventTypeRepository
     * @param ThemeRepositoryInterface $themeRepository
     * @param MeetRepositoryInterface $meetRepository
     * @param Calendar $calendarHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        LocationFactory $locationFactory,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        EventTypeRepositoryInterface $eventTypeRepository,
        ThemeRepositoryInterface $themeRepository,
        MeetRepositoryInterface $meetRepository,
        Calendar $calendarHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->locationFactory = $locationFactory;
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->themeRepository = $themeRepository;
        $this->meetRepository = $meetRepository;
        $this->calendarHelper = $calendarHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Set meet
     *
     * @param MeetInterface $event
     * @return $this
     */
    public function setEvent(MeetInterface $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get meet
     *
     * @return MeetInterface|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Get location name
     *
     * @return string
     */
    public function getStoreName()
    {
        if (!$this->event) {
            return '';
        }

        try {
            $location = $this->locationFactory->create()->load($this->event->getLocationId());
            if ($location->getId()) {
                $name = $location->getName();
                return $name;
            }
            return '';
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

        $meetTypeCode = $this->event->getMeetType();
        if (empty($meetTypeCode)) {
            return '';
        }

        try {
            $eventType = $this->eventTypeRepository->getByCode($meetTypeCode);
            return __($eventType->getName());
        } catch (\Exception $e) {
            // Fallback to code if event type not found
            return $meetTypeCode;
        }
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

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
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
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
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
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
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
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get registration URL
     *
     * @param int $meetId
     * @return string
     */
    public function getRegisterUrl($meetId)
    {
        return $this->getUrl('events/index/register', ['_query' => ['meetId' => $meetId]]);
    }

    /**
     * Get unregister URL
     *
     * @param int $meetId
     * @return string
     */
    public function getUnregisterUrl($meetId)
    {
        return $this->getUrl('events/index/unregister', ['_query' => ['meetId' => $meetId]]);
    }

    /**
     * Get login URL with return URL parameter
     *
     * @return string
     */
    public function getLoginUrlWithReturn()
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        return $this->getUrl('customer/account/login', ['_query' => ['referer' => base64_encode($currentUrl)]]);
    }

    /**
     * Get event description with HTML allowed
     *
     * @return string
     */
    public function getDescriptionHtml()
    {
        if (!$this->event) {
            return '';
        }

        return $this->event->getDescription() ?: '';
    }

    /**
     * Check if event is recurring
     *
     * @return bool
     */
    public function isRecurring()
    {
        if (!$this->event) {
            return false;
        }

        return $this->event->getRecurrenceType() !== MeetInterface::RECURRENCE_TYPE_NONE;
    }

    /**
     * Get next occurrence date for recurring events
     *
     * @return string|null
     */
    public function getNextOccurrenceDate()
    {
        if (!$this->isRecurring() || !$this->event) {
            return null;
        }

        $startDate = new \DateTime($this->event->getStartDate());
        $now = new \DateTime();
        $recurrenceType = $this->event->getRecurrenceType();

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
     * Get periodicity label
     *
     * @return string|null
     */
    public function getPeriodicityLabel()
    {
        if (!$this->isRecurring() || !$this->event) {
            return null;
        }

        $recurrenceType = $this->event->getRecurrenceType();

        if ($recurrenceType === MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            return __('Biweekly');
        } elseif ($recurrenceType === MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            return __('Weekly');
        }

        return null;
    }

    /**
     * Format event date display (handles recurring events)
     *
     * @return string
     */
    public function getFormattedEventDate()
    {
        if (!$this->event) {
            return '';
        }

        if ($this->isRecurring()) {
            $nextDate = $this->getNextOccurrenceDate();
            $periodicity = $this->getPeriodicityLabel();
            
            if ($nextDate && $periodicity) {
                $formattedDate = $this->formatEventDate($nextDate);
                return $formattedDate . ' (' . $periodicity . ')';
            }
        }

        return $this->formatEventDate($this->event->getStartDate());
    }

    /**
     * Get theme name for the event
     *
     * @return string|null
     */
    public function getThemeName()
    {
        if (!$this->event || !$this->event->getThemeId()) {
            return null;
        }

        try {
            $theme = $this->themeRepository->getById($this->event->getThemeId());
            return $theme->getName();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get event detail URL
     *
     * @param int $meetId
     * @return string
     */
    public function getEventViewUrl($meetId)
    {
        return $this->getUrl('events/index/view', ['id' => $meetId]);
    }

    /**
     * Get meet repository (for view template)
     *
     * @return MeetRepositoryInterface
     */
    public function getMeetRepository()
    {
        return $this->meetRepository;
    }

    /**
     * Get iCal download URL for current event
     *
     * @return string
     */
    public function getCalendarIcalUrl()
    {
        if (!$this->event) {
            return '';
        }
        return $this->calendarHelper->getIcalUrl($this->event->getMeetId());
    }

    /**
     * Get Google Calendar URL for current event
     *
     * @return string
     */
    public function getCalendarGoogleUrl()
    {
        if (!$this->event) {
            return '';
        }

        // Load location
        $location = null;
        try {
            $location = $this->locationFactory->create()->load($this->event->getLocationId());
            if (!$location->getId()) {
                $location = null;
            }
        } catch (\Exception $e) {
            $location = null;
        }

        return $this->calendarHelper->getGoogleCalendarUrl($this->event, $location);
    }

    /**
     * Check if calendar links should be shown
     * Only show for registered users with confirmed status
     *
     * @return bool
     */
    public function canShowCalendarLinks()
    {
        if (!$this->event || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $status = $this->getRegistrationStatus();
        return $status === RegistrationInterface::STATUS_CONFIRMED;
    }

    /**
     * Get update phone URL
     *
     * @param int $meetId
     * @return string
     */
    public function getUpdatePhoneUrl($meetId)
    {
        return $this->getUrl('events/index/updatephone', ['meetId' => $meetId]);
    }

    /**
     * Check if current registration has phone number
     *
     * @return bool
     */
    public function hasPhoneNumber()
    {
        if (!$this->event || !$this->isCustomerRegistered()) {
            return false;
        }

        $status = $this->getRegistrationStatus();
        if (!$status) {
            return false;
        }

        $customerId = $this->customerSession->getCustomerId();
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        if ($collection->getTotalCount() > 0) {
            $items = $collection->getItems();
            $registration = reset($items);
            $phoneNumber = $registration->getPhoneNumber();
            return !empty($phoneNumber);
        }

        return false;
    }

    /**
     * Get full info URL for a meet
     *
     * @param MeetInterface|null $meet
     * @return string|null
     */
    public function getInfoUrl($meet = null)
    {
        if (!$meet) {
            $meet = $this->event;
        }
        
        if (!$meet) {
            return null;
        }

        $path = $meet->getInfoUrlPath();
        if (empty($path)) {
            return null;
        }

        // Get store base URL and construct full URL
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $path = ltrim($path, '/');
        
        return $baseUrl . $path;
    }

    /**
     * Check if meet has info URL
     *
     * @param MeetInterface|null $meet
     * @return bool
     */
    public function hasInfoUrl($meet = null)
    {
        if (!$meet) {
            $meet = $this->event;
        }
        
        if (!$meet) {
            return false;
        }

        $path = $meet->getInfoUrlPath();
        return !empty($path);
    }
}

