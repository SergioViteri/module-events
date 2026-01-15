<?php
/**
 * Zacatrus Events Attendance Check Block
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Attendance;

use Zaca\Events\Api\Data\RegistrationInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zaca\Events\Helper\Data as EventsHelper;

class Check extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ManagerInterface $messageManager
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param EventsHelper $eventsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ManagerInterface $messageManager,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        EventsHelper $eventsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->messageManager = $messageManager;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Get registration
     *
     * @return RegistrationInterface|null
     */
    public function getRegistration()
    {
        return $this->registry->registry('current_registration');
    }

    /**
     * Get meet
     *
     * @return MeetInterface|null
     */
    public function getMeet()
    {
        return $this->registry->registry('current_meet');
    }

    /**
     * Get has location code
     *
     * @return bool
     */
    public function getHasLocationCode()
    {
        return (bool) $this->registry->registry('has_location_code');
    }

    /**
     * Get location ID
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->registry->registry('session_location_id');
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return (string) $this->registry->registry('customer_name');
    }

    /**
     * Get attendance check URL
     *
     * @return string
     */
    public function getAttendanceCheckUrl()
    {
        $registration = $this->getRegistration();
        if (!$registration) {
            return '';
        }
        $routePath = $this->eventsHelper->getRoutePath();
        return $this->getUrl($routePath . '/index/attendance', ['registrationId' => $registration->getRegistrationId()]);
    }

    /**
     * Format date and time
     *
     * @param string $date UTC date string
     * @return string
     */
    public function formatDateTime($date)
    {
        // Convert UTC date to store timezone for display
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        // Parse UTC date and convert to store timezone
        $dateObj = new \DateTime($date, new \DateTimeZone('UTC'));
        $dateObj->setTimezone($timezoneObj);
        
        return $dateObj->format('d/m/Y H:i');
    }

    /**
     * Get messages HTML
     *
     * @return string
     */
    public function getMessagesHtml()
    {
        $messages = $this->messageManager->getMessages(true);
        if ($messages->getCount() > 0) {
            $messagesBlock = $this->getLayout()->getBlock('messages');
            if ($messagesBlock) {
                return $messagesBlock->setMessages($messages)->getGroupedHtml();
            }
        }
        return '';
    }

    /**
     * Check if event is recurring
     *
     * @return bool
     */
    public function isRecurring()
    {
        $meet = $this->getMeet();
        if (!$meet) {
            return false;
        }

        return $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
    }

    /**
     * Check if event has an end date to display
     *
     * @return bool
     */
    public function hasEndDate()
    {
        $meet = $this->getMeet();
        if (!$meet) {
            return false;
        }

        // Only show end date for recurring events that have an end_date set
        return $this->isRecurring() && !empty($meet->getEndDate());
    }

    /**
     * Format event date display (handles recurring events)
     *
     * @return string
     */
    public function getFormattedEventDate()
    {
        $meet = $this->getMeet();
        if (!$meet) {
            return '';
        }

        // Convert UTC date to store timezone for display
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);

        if ($this->isRecurring()) {
            // For recurring events, calculate next occurrence
            $startDate = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
            $startDate->setTimezone($timezoneObj);
            
            $now = new \DateTime('now', $timezoneObj);
            $recurrenceType = $meet->getRecurrenceType();

            // Calculate next occurrence
            $nextDate = clone $startDate;

            if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
                // Biweekly (every 15 days)
                while ($nextDate <= $now) {
                    $nextDate->modify('+15 days');
                }
            } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
                // Weekly (every 7 days)
                while ($nextDate <= $now) {
                    $nextDate->modify('+7 days');
                }
            }

            $periodicity = '';
            if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
                $periodicity = __('Biweekly');
            } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
                $periodicity = __('Weekly');
            }

            if ($periodicity) {
                return $nextDate->format('d/m/Y H:i') . ' (' . $periodicity->render() . ')';
            }
        }

        // For non-recurring events, just format the start date
        $dateObj = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
        $dateObj->setTimezone($timezoneObj);
        
        return $dateObj->format('d/m/Y H:i');
    }

    /**
     * Get formatted end date for recurring events (date only, no time)
     *
     * @return string|null
     */
    public function getFormattedEventEndDate()
    {
        if (!$this->hasEndDate()) {
            return null;
        }

        $meet = $this->getMeet();
        
        // Convert UTC date to store timezone for display
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        // Parse UTC date and convert to store timezone
        $dateObj = new \DateTime($meet->getEndDate(), new \DateTimeZone('UTC'));
        $dateObj->setTimezone($timezoneObj);
        
        // Return only date part (no time)
        return $dateObj->format('d/m/Y');
    }

    /**
     * Get day of the week for recurring events (based on start date)
     *
     * @return string|null
     */
    public function getRecurringDayOfWeek()
    {
        if (!$this->isRecurring()) {
            return null;
        }

        $meet = $this->getMeet();
        
        // Convert UTC date to store timezone for display
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        // Parse UTC date and convert to store timezone
        $dateObj = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
        $dateObj->setTimezone($timezoneObj);
        
        // Get day of week name (localized)
        $dayNumber = (int) $dateObj->format('w'); // 0 = Sunday, 6 = Saturday
        $days = [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday')
        ];
        
        return $days[$dayNumber] ?? null;
    }

    /**
     * Get next occurrence date for recurring events (formatted)
     *
     * @return string|null
     */
    public function getNextOccurrenceDate()
    {
        if (!$this->isRecurring()) {
            return null;
        }

        $meet = $this->getMeet();
        
        // Convert UTC date to store timezone for display
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);

        // For recurring events, calculate next occurrence
        $startDate = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
        $startDate->setTimezone($timezoneObj);
        
        $now = new \DateTime('now', $timezoneObj);
        $recurrenceType = $meet->getRecurrenceType();

        // Calculate next occurrence
        $nextDate = clone $startDate;

        if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            // Biweekly (every 15 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+15 days');
            }
        } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            // Weekly (every 7 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+7 days');
            }
        }

        return $nextDate->format('d/m/Y H:i');
    }
}
