<?php
/**
 * Zacatrus Events Attendance Validator Service
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Service;

use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Model\RegistrationFactory;
use Zaca\Events\Model\AttendanceFactory;
use Zaca\Events\Model\ResourceModel\Attendance\CollectionFactory as AttendanceCollectionFactory;
use Zaca\Events\Model\ResourceModel\Registration as RegistrationResourceModel;
use Psr\Log\LoggerInterface;

class AttendanceValidator
{
    /**
     * Days between quincenal meets
     */
    const QUINCENAL_DAYS = 15;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var AttendanceFactory
     */
    protected $attendanceFactory;

    /**
     * @var AttendanceCollectionFactory
     */
    protected $attendanceCollectionFactory;

    /**
     * @var RegistrationResourceModel
     */
    protected $registrationResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LocationFactory $locationFactory
     * @param RegistrationFactory $registrationFactory
     * @param AttendanceFactory $attendanceFactory
     * @param AttendanceCollectionFactory $attendanceCollectionFactory
     * @param RegistrationResourceModel $registrationResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        LocationFactory $locationFactory,
        RegistrationFactory $registrationFactory,
        AttendanceFactory $attendanceFactory,
        AttendanceCollectionFactory $attendanceCollectionFactory,
        RegistrationResourceModel $registrationResource,
        LoggerInterface $logger
    ) {
        $this->locationFactory = $locationFactory;
        $this->registrationFactory = $registrationFactory;
        $this->attendanceFactory = $attendanceFactory;
        $this->attendanceCollectionFactory = $attendanceCollectionFactory;
        $this->registrationResource = $registrationResource;
        $this->logger = $logger;
    }

    /**
     * Validate location code
     *
     * @param string $code
     * @param int $locationId
     * @return bool
     */
    public function validateLocationCode(string $code, int $locationId): bool
    {
        try {
            $location = $this->locationFactory->create()->load($locationId);
            if (!$location->getId()) {
                return false;
            }

            $locationCode = $location->getCode();
            if (empty($locationCode)) {
                return false;
            }

            // Case-sensitive comparison for security
            return $locationCode === $code;
        } catch (\Exception $e) {
            $this->logger->error('[Attendance Validator] Error validating location code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if date is valid for meet (handles recurrence)
     *
     * @param MeetInterface $meet
     * @param \DateTime $checkDate
     * @return bool
     */
    public function isDateValidForMeet(MeetInterface $meet, \DateTime $checkDate): bool
    {
        try {
            $startDate = $meet->getStartDate();
            if (empty($startDate)) {
                $this->logger->error('[Attendance Validator] Meet start date is empty for meet ID: ' . $meet->getMeetId());
                return false;
            }
            
            $meetStartDate = new \DateTime($startDate);
            $checkDateOnly = clone $checkDate;
            $checkDateOnly->setTime(0, 0, 0);
            $meetDateOnly = clone $meetStartDate;
            $meetDateOnly->setTime(0, 0, 0);

            // For non-recurring meets, check if dates match (same day)
            if ($meet->getRecurrenceType() === MeetInterface::RECURRENCE_TYPE_NONE) {
                return $checkDateOnly->format('Y-m-d') === $meetDateOnly->format('Y-m-d');
            }

            // For quincenal (biweekly) recurrence
            if ($meet->getRecurrenceType() === MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
                // Calculate all valid dates: start_date, start_date + 15 days, + 30 days, etc.
                $validDates = $this->calculateRecurringDates($meetStartDate);
                
                foreach ($validDates as $validDate) {
                    $validDateOnly = clone $validDate;
                    $validDateOnly->setTime(0, 0, 0);
                    if ($checkDateOnly->format('Y-m-d') === $validDateOnly->format('Y-m-d')) {
                        return true;
                    }
                }
                return false;
            }

            // For semanal (weekly) recurrence
            if ($meet->getRecurrenceType() === MeetInterface::RECURRENCE_TYPE_SEMANAL) {
                // Calculate all valid dates: start_date, start_date + 7 days, + 14 days, etc.
                $validDates = $this->calculateWeeklyRecurringDates($meetStartDate);
                
                foreach ($validDates as $validDate) {
                    $validDateOnly = clone $validDate;
                    $validDateOnly->setTime(0, 0, 0);
                    if ($checkDateOnly->format('Y-m-d') === $validDateOnly->format('Y-m-d')) {
                        return true;
                    }
                }
                return false;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('[Attendance Validator] Error validating date: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate recurring dates for quincenal meets
     *
     * @param \DateTime $startDate
     * @return \DateTime[]
     */
    protected function calculateRecurringDates(\DateTime $startDate): array
    {
        $validDates = [];
        $endDate = clone $startDate;
        $endDate->modify('+6 months'); // Check up to 6 months ahead

        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $validDates[] = clone $currentDate;
            $currentDate->modify('+' . self::QUINCENAL_DAYS . ' days');
        }

        return $validDates;
    }

    /**
     * Calculate recurring dates for weekly meets
     *
     * @param \DateTime $startDate
     * @return \DateTime[]
     */
    protected function calculateWeeklyRecurringDates(\DateTime $startDate): array
    {
        $validDates = [];
        $endDate = clone $startDate;
        $endDate->modify('+6 months'); // Check up to 6 months ahead

        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $validDates[] = clone $currentDate;
            $currentDate->modify('+7 days');
        }

        return $validDates;
    }

    /**
     * Check if registration has already attended today
     *
     * @param int $registrationId
     * @param \DateTime $date
     * @return bool
     */
    public function hasAttendedToday(int $registrationId, \DateTime $date): bool
    {
        try {
            $dateOnly = $date->format('Y-m-d');
            
            $collection = $this->attendanceCollectionFactory->create();
            $collection->addFieldToFilter('registration_id', $registrationId)
                ->addFieldToFilter('attendance_date', $dateOnly);

            return $collection->getSize() > 0;
        } catch (\Exception $e) {
            $this->logger->error('[Attendance Validator] Error checking duplicate attendance: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record attendance and increment count
     *
     * @param int $registrationId
     * @param int $locationId
     * @return bool
     */
    public function recordAttendance(int $registrationId, int $locationId): bool
    {
        try {
            // Load registration
            $registration = $this->registrationFactory->create();
            $this->registrationResource->load($registration, $registrationId);
            
            if (!$registration->getId()) {
                $this->logger->error('[Attendance Validator] Registration not found: ' . $registrationId);
                return false;
            }

            // Create attendance record
            $attendance = $this->attendanceFactory->create();
            $attendance->setRegistrationId($registrationId)
                ->setLocationId($locationId)
                ->setAttendanceDate(date('Y-m-d'))
                ->save();

            // Increment attendance count
            $currentCount = $registration->getAttendanceCount();
            $registration->setAttendanceCount($currentCount + 1);
            $this->registrationResource->save($registration);

            $this->logger->info(
                '[Attendance Validator] Attendance recorded for registration ID: ' . $registrationId . 
                ', Location ID: ' . $locationId . 
                ', New count: ' . ($currentCount + 1)
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('[Attendance Validator] Error recording attendance: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove attendance and decrement count
     *
     * @param int $registrationId
     * @return bool
     */
    public function removeAttendance(int $registrationId): bool
    {
        try {
            // Load registration
            $registration = $this->registrationFactory->create();
            $this->registrationResource->load($registration, $registrationId);
            
            if (!$registration->getId()) {
                $this->logger->error('[Attendance Validator] Registration not found: ' . $registrationId);
                return false;
            }

            // Find the most recent attendance record
            $collection = $this->attendanceCollectionFactory->create();
            $collection->addFieldToFilter('registration_id', $registrationId)
                ->setOrder('attendance_date', 'DESC')
                ->setOrder('created_at', 'DESC')
                ->setPageSize(1);

            if ($collection->getSize() === 0) {
                $this->logger->warning('[Attendance Validator] No attendance records found for registration ID: ' . $registrationId);
                return false;
            }

            // Delete the most recent attendance record
            $attendance = $collection->getFirstItem();
            $attendance->delete();

            // Decrement attendance count (but not below 0)
            $currentCount = $registration->getAttendanceCount();
            $newCount = max(0, $currentCount - 1);
            $registration->setAttendanceCount($newCount);
            $this->registrationResource->save($registration);

            $this->logger->info(
                '[Attendance Validator] Attendance removed for registration ID: ' . $registrationId . 
                ', Old count: ' . $currentCount . 
                ', New count: ' . $newCount
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('[Attendance Validator] Error removing attendance: ' . $e->getMessage());
            return false;
        }
    }
}

