<?php
/**
 * Zacatrus Events Send Reminders Cron Job
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Cron;

use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\Data\RegistrationInterface;
use Zaca\Events\Model\ResourceModel\Registration\CollectionFactory as RegistrationCollectionFactory;
use Zaca\Events\Model\ResourceModel\ReminderSent\CollectionFactory as ReminderSentCollectionFactory;
use Zaca\Events\Model\ReminderSentFactory;
use Zaca\Events\Helper\Email as EmailHelper;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class SendReminders
{
    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var RegistrationCollectionFactory
     */
    protected $registrationCollectionFactory;

    /**
     * @var ReminderSentCollectionFactory
     */
    protected $reminderSentCollectionFactory;

    /**
     * @var ReminderSentFactory
     */
    protected $reminderSentFactory;

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @param MeetRepositoryInterface $meetRepository
     * @param RegistrationCollectionFactory $registrationCollectionFactory
     * @param ReminderSentCollectionFactory $reminderSentCollectionFactory
     * @param ReminderSentFactory $reminderSentFactory
     * @param EmailHelper $emailHelper
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        MeetRepositoryInterface $meetRepository,
        RegistrationCollectionFactory $registrationCollectionFactory,
        ReminderSentCollectionFactory $reminderSentCollectionFactory,
        ReminderSentFactory $reminderSentFactory,
        EmailHelper $emailHelper,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        RegistrationRepositoryInterface $registrationRepository,
        LoggerInterface $logger
    ) {
        $this->meetRepository = $meetRepository;
        $this->registrationCollectionFactory = $registrationCollectionFactory;
        $this->reminderSentCollectionFactory = $reminderSentCollectionFactory;
        $this->reminderSentFactory = $reminderSentFactory;
        $this->emailHelper = $emailHelper;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->registrationRepository = $registrationRepository;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('[Events Reminders] Starting reminder cron job');

        try {
            // Get all active meets with reminder_days set
            $meets = $this->getActiveMeetsWithReminders();

            $totalSent = 0;
            foreach ($meets as $meet) {
                $reminderDays = $this->parseReminderDays($meet->getReminderDays());
                if (empty($reminderDays)) {
                    continue;
                }

                $sent = $this->processMeetReminders($meet, $reminderDays);
                $totalSent += $sent;
            }

            $this->logger->info('[Events Reminders] Cron job completed. Total reminders sent: ' . $totalSent);
        } catch (\Exception $e) {
            $this->logger->error('[Events Reminders] Error in cron job: ' . $e->getMessage());
            $this->logger->error('[Events Reminders] Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Get active meets with reminder days configured
     *
     * @return \Zaca\Events\Api\Data\MeetInterface[]
     */
    protected function getActiveMeetsWithReminders()
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->addFilter('reminder_days', null, 'neq')
            ->create();

        $result = $this->meetRepository->getList($searchCriteria);
        return $result->getItems();
    }

    /**
     * Parse reminder days string into array of integers
     *
     * @param string|null $reminderDays
     * @return int[]
     */
    protected function parseReminderDays($reminderDays)
    {
        if (empty($reminderDays)) {
            return [];
        }

        // Remove spaces and split by comma
        $cleaned = preg_replace('/\s+/', '', $reminderDays);
        $parts = explode(',', $cleaned);
        $days = [];

        foreach ($parts as $part) {
            $day = (int) $part;
            if ($day > 0) {
                $days[] = $day;
            }
        }

        return array_unique($days);
    }

    /**
     * Process reminders for a specific meet
     *
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param int[] $reminderDays
     * @return int Number of reminders sent
     */
    protected function processMeetReminders($meet, array $reminderDays)
    {
        $sent = 0;
        $now = new \DateTime();
        $now->setTime(0, 0, 0); // Set to start of day for accurate day calculations
        
        $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
        
        if ($isRecurring) {
            // For recurring events, process each future occurrence
            $occurrences = $this->getFutureOccurrences($meet, $now);
            
            foreach ($occurrences as $occurrenceDate) {
                $daysUntilOccurrence = (int) $now->diff($occurrenceDate)->days;
                
                // Check if today matches any reminder day for this occurrence
                if (in_array($daysUntilOccurrence, $reminderDays)) {
                    $sent += $this->sendRemindersForOccurrence($meet, $occurrenceDate, $daysUntilOccurrence);
                }
            }
        } else {
            // For non-recurring events, use original logic
            $startDate = new \DateTime($meet->getStartDate());
            $startDate->setTime(0, 0, 0);
            
            // Only process if event is in the future
            if ($startDate <= $now) {
                return 0;
            }
            
            $daysUntilEvent = (int) $now->diff($startDate)->days;
            
            // Check if today matches any reminder day
            if (in_array($daysUntilEvent, $reminderDays)) {
                $sent = $this->sendRemindersForOccurrence($meet, $startDate, $daysUntilEvent);
            }
        }
        
        return $sent;
    }

    /**
     * Get all future occurrences for a recurring event
     *
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param \DateTime $now
     * @return \DateTime[]
     */
    protected function getFutureOccurrences($meet, \DateTime $now)
    {
        $occurrences = [];
        $startDate = new \DateTime($meet->getStartDate());
        $startDate->setTime(0, 0, 0);
        
        $recurrenceType = $meet->getRecurrenceType();
        $endDate = $meet->getEndDate() ? new \DateTime($meet->getEndDate()) : null;
        if ($endDate) {
            $endDate->setTime(23, 59, 59); // End of day
        }
        
        // Calculate limit date (max 1 year ahead or end_date, whichever comes first)
        $limitDate = clone $now;
        $limitDate->modify('+1 month');
        if ($endDate && $endDate < $limitDate) {
            $limitDate = $endDate;
        }
        
        $currentDate = clone $startDate;
        
        if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            // Biweekly (every 15 days)
            while ($currentDate <= $limitDate) {
                if ($currentDate >= $now) {
                    $occurrences[] = clone $currentDate;
                }
                $currentDate->modify('+15 days');
            }
        } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            // Weekly (every 7 days)
            while ($currentDate <= $limitDate) {
                if ($currentDate >= $now) {
                    $occurrences[] = clone $currentDate;
                }
                $currentDate->modify('+7 days');
            }
        }
        
        return $occurrences;
    }

    /**
     * Send reminders for a specific occurrence date
     *
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param \DateTime $occurrenceDate
     * @param int $daysUntilOccurrence
     * @return int Number of reminders sent
     */
    protected function sendRemindersForOccurrence($meet, \DateTime $occurrenceDate, int $daysUntilOccurrence)
    {
        $sent = 0;
        
        // Get confirmed registrations where reminders are not disabled
        $registrations = $this->registrationCollectionFactory->create();
        $registrations->addFieldToFilter('meet_id', $meet->getMeetId())
            ->addFieldToFilter('status', RegistrationInterface::STATUS_CONFIRMED)
            ->addFieldToFilter('email_reminders_disabled', 0);
        
        foreach ($registrations as $registration) {
            // Check if reminder for this occurrence has already been sent
            // Use occurrence date to uniquely identify which occurrence
            if ($this->isReminderAlreadySentForOccurrence($registration->getRegistrationId(), $occurrenceDate, $daysUntilOccurrence)) {
                continue;
            }
            
            // Send reminder email
            if ($this->emailHelper->sendReminderEmail($registration, $daysUntilOccurrence)) {   
                // Save registration if unsubscribe code was generated
                if ($registration->getUnsubscribeCode()) {
                    try {
                        $this->registrationRepository->save($registration);
                    } catch (\Exception $e) {
                        $this->logger->error(
                            '[Events Reminders] Error saving registration unsubscribe code: ' . $e->getMessage()
                        );
                    }
                }
                // Record in reminder_sent table with occurrence date info
                $this->recordReminderSentForOccurrence($registration->getRegistrationId(), $occurrenceDate, $daysUntilOccurrence);
                $sent++;
            }
        }
        
        return $sent;
    }

    /**
     * Check if reminder has already been sent for a specific occurrence
     *
     * @param int $registrationId
     * @param \DateTime $occurrenceDate
     * @param int $reminderDays
     * @return bool
     */
    protected function isReminderAlreadySentForOccurrence($registrationId, \DateTime $occurrenceDate, $reminderDays)
    {
        // Calculate the date when the reminder should have been sent
        // (occurrence date minus reminder days)
        $reminderSentDate = clone $occurrenceDate;
        $reminderSentDate->modify('-' . $reminderDays . ' days');
        $reminderSentDate->setTime(0, 0, 0);
        
        // Check if we've sent a reminder with this reminder_days value
        // where the sent_at date matches when we should have sent it for this occurrence
        $collection = $this->reminderSentCollectionFactory->create();
        $collection->addFieldToFilter('registration_id', $registrationId)
            ->addFieldToFilter('reminder_days', $reminderDays);
        
        // Check if any sent reminder corresponds to this occurrence
        // (sent_at should be on or after the calculated reminder sent date)
        foreach ($collection as $reminderSent) {
            $sentAt = new \DateTime($reminderSent->getSentAt());
            $sentAt->setTime(0, 0, 0);
            
            // If sent on the same day as when we should send for this occurrence, it's a match
            if ($sentAt == $reminderSentDate) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Record that reminder was sent for a specific occurrence
     *
     * @param int $registrationId
     * @param \DateTime $occurrenceDate
     * @param int $reminderDays
     * @return void
     */
    protected function recordReminderSentForOccurrence($registrationId, \DateTime $occurrenceDate, $reminderDays)
    {
        try {
            $reminderSent = $this->reminderSentFactory->create();
            $reminderSent->setRegistrationId($registrationId)
                ->setReminderDays($reminderDays)
                ->save();
        } catch (\Exception $e) {
            $this->logger->error('[Events Reminders] Error recording reminder sent: ' . $e->getMessage());
        }
    }
}

