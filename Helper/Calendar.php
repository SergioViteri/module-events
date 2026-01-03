<?php
/**
 * Zacatrus Events Calendar Helper
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Helper;

use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Model\Location;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Calendar extends AbstractHelper
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
    }

    /**
     * Get iCal download URL for a meet
     *
     * @param int $meetId
     * @return string
     */
    public function getIcalUrl($meetId)
    {
        return $this->urlBuilder->getUrl(
            'events/index/calendar',
            ['id' => $meetId],
            ['_secure' => true]
        );
    }

    /**
     * Get Google Calendar URL for a meet
     *
     * @param MeetInterface $meet
     * @param Location|null $location
     * @return string
     */
    public function getGoogleCalendarUrl(MeetInterface $meet, $location = null)
    {
        // Get store timezone
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
        
        // For recurring events, use next occurrence date; otherwise use start date
        if ($isRecurring) {
            $nextOccurrence = $this->getNextOccurrenceDate($meet, $timezoneObj);
            if ($nextOccurrence) {
                $startDate = $nextOccurrence;
            } else {
                $startDate = new \DateTime($meet->getStartDate(), $timezoneObj);
            }
        } else {
            $startDate = new \DateTime($meet->getStartDate(), $timezoneObj);
        }
        
        // Format dates for Google Calendar using local timezone
        // Google Calendar accepts local time format (YYYYMMDDTHHmmss) without Z
        $startFormatted = $startDate->format('Ymd\THis');

        // Calculate end time
        // Always use duration_minutes for the event duration (end_date is for recurrence series end, not event duration)
        $endDate = clone $startDate;
        $durationMinutes = $meet->getDurationMinutes() ?: 60; // Default to 60 minutes
        $endDate->modify('+' . $durationMinutes . ' minutes');
        $endFormatted = $endDate->format('Ymd\THis');

        // Build location string
        $locationStr = '';
        if ($location) {
            $addressParts = [];
            if ($location->getName()) {
                $addressParts[] = $location->getName();
            }
            if ($location->getAddress()) {
                $addressParts[] = $location->getAddress();
            }
            if ($location->getPostalCode()) {
                $addressParts[] = $location->getPostalCode();
            }
            if ($location->getCity()) {
                $addressParts[] = $location->getCity();
            }
            $locationStr = implode(', ', $addressParts);
        }

        // Build description
        $description = $meet->getDescription() ?: '';
        // Escape HTML and limit length
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8'); // Decode HTML entities first
        $description = strip_tags($description); // Remove HTML tags
        $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); // Escape HTML
        $description = mb_substr($description, 0, 1000); // Google Calendar has limits

        // Build Google Calendar URL
        $params = [
            'action' => 'TEMPLATE',
            'text' => $meet->getName(),
            'dates' => $startFormatted . '/' . $endFormatted,
            'details' => $description,
            'location' => $locationStr,
            'sf' => 'true',
            'output' => 'xml'
        ];

        $url = 'https://calendar.google.com/calendar/render?' . http_build_query($params);
        return $url;
    }

    /**
     * Get next occurrence date for recurring events
     *
     * @param MeetInterface $meet
     * @param \DateTimeZone $timezoneObj
     * @return \DateTime|null
     */
    protected function getNextOccurrenceDate(MeetInterface $meet, \DateTimeZone $timezoneObj)
    {
        $recurrenceType = $meet->getRecurrenceType();
        
        if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE) {
            return null;
        }

        $startDate = new \DateTime($meet->getStartDate(), $timezoneObj);
        $now = new \DateTime('now', $timezoneObj);

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
        } else {
            return null;
        }

        return $nextDate;
    }

    /**
     * Generate iCal file content (RFC 5545 format)
     *
     * @param MeetInterface $meet
     * @param Location|null $location
     * @return string
     */
    public function generateIcalContent(MeetInterface $meet, $location = null)
    {
        // Get store timezone - use same approach as Google Calendar
        $store = $this->storeManager->getStore();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);
        
        $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
        
        // For recurring events, use next occurrence date; otherwise use start date
        if ($isRecurring) {
            $nextOccurrence = $this->getNextOccurrenceDate($meet, $timezoneObj);
            if ($nextOccurrence) {
                $startDate = $nextOccurrence;
            } else {
                $startDate = new \DateTime($meet->getStartDate(), $timezoneObj);
            }
        } else {
            $startDate = new \DateTime($meet->getStartDate(), $timezoneObj);
        }
        
        $startFormatted = $startDate->format('Ymd\THis');

        // Calculate end time
        // For non-recurring events, always use duration_minutes (single day event)
        // For recurring events, use duration_minutes for each occurrence
        $endDate = clone $startDate;
        
        if ($isRecurring && $meet->getEndDate()) {
            // For recurring events with end_date, use it as UNTIL in RRULE
            // But for DTEND, use duration_minutes from start date
            $durationMinutes = $meet->getDurationMinutes() ?: 60;
            $endDate->modify('+' . $durationMinutes . ' minutes');
        } else {
            // For non-recurring events or when end_date is not set, use duration_minutes
            $durationMinutes = $meet->getDurationMinutes() ?: 60; // Default to 60 minutes
            $endDate->modify('+' . $durationMinutes . ' minutes');
        }
        $endFormatted = $endDate->format('Ymd\THis');

        // Build location string
        $locationStr = '';
        if ($location) {
            $addressParts = [];
            if ($location->getName()) {
                $addressParts[] = $location->getName();
            }
            if ($location->getAddress()) {
                $addressParts[] = $location->getAddress();
            }
            if ($location->getPostalCode()) {
                $addressParts[] = $location->getPostalCode();
            }
            if ($location->getCity()) {
                $addressParts[] = $location->getCity();
            }
            $locationStr = implode(', ', $addressParts);
        }

        // Build description for iCal (plain text, not HTML)
        $description = $meet->getDescription() ?: '';
        $description = strip_tags($description); // Remove HTML tags
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8'); // Decode HTML entities
        // Escape special characters for iCal format
        // Note: iCal uses backslash escaping, not HTML escaping
        $description = str_replace(["\r\n", "\r", "\n"], "\\n", $description);
        $description = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $description);

        // Escape location for iCal format (plain text, not HTML)
        // Escape special characters for iCal format
        // Note: iCal uses backslash escaping, not HTML escaping
        $locationStr = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $locationStr);

        // Escape summary (event name) for iCal format
        $summary = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $meet->getName());

        // Generate unique ID
        $uid = 'meet-' . $meet->getMeetId() . '@' . parse_url($this->urlBuilder->getBaseUrl(), PHP_URL_HOST);

        // Get timezone identifier for iCal (e.g., Europe/Madrid)
        $tzid = $timezone;
        
        // Build RRULE for recurring events
        // Note: Google Calendar URL format doesn't support recurrence, only iCal files do
        $rrule = '';
        if ($isRecurring) {
            $recurrenceType = $meet->getRecurrenceType();
            if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
                // Biweekly (every 15 days) - use DAILY with INTERVAL=15
                $rrule = 'FREQ=DAILY;INTERVAL=15';
            } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
                // Weekly (every 7 days) - use WEEKLY with INTERVAL=1
                $rrule = 'FREQ=WEEKLY;INTERVAL=1';
            }
            
            // Add UNTIL if end_date is set (format: YYYYMMDDTHHmmss)
            if ($meet->getEndDate() && $rrule) {
                $untilDate = new \DateTime($meet->getEndDate(), $timezoneObj);
                // For UNTIL in RRULE, we need to use the same format as DTSTART (local time with TZID)
                $untilFormatted = $untilDate->format('Ymd\THis');
                $rrule .= ';UNTIL=' . $untilFormatted;
            }
        }
        
        // Build iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Zacatrus Events//NONSGML v1.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        // Note: Google Calendar URL format doesn't support recurrence
        // For recurring events, users should use the iCal file download instead
        
        // Build VTIMEZONE definition
        // Use minimal VTIMEZONE - most calendar applications (including Google Calendar)
        // can resolve standard IANA timezone identifiers
        $ical .= "BEGIN:VTIMEZONE\r\n";
        $ical .= "TZID:" . $tzid . "\r\n";
        $ical .= "END:VTIMEZONE\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $uid . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART;TZID=" . $tzid . ":" . $startFormatted . "\r\n";
        $ical .= "DTEND;TZID=" . $tzid . ":" . $endFormatted . "\r\n";
        if ($rrule) {
            $ical .= "RRULE:" . $rrule . "\r\n";
        }
        $ical .= "SUMMARY:" . $summary . "\r\n";
        if ($description) {
            $ical .= "DESCRIPTION:" . $description . "\r\n";
        }
        if ($locationStr) {
            $ical .= "LOCATION:" . $locationStr . "\r\n";
        }
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "SEQUENCE:0\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }
}

