<?php
/**
 * Zacatrus Events Registration Model
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\Registration as RegistrationResourceModel;
use Zaca\Events\Api\Data\RegistrationInterface;
use Magento\Framework\Model\AbstractModel;

class Registration extends AbstractModel implements RegistrationInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(RegistrationResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getRegistrationId()
    {
        return $this->getData(self::REGISTRATION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRegistrationId($registrationId)
    {
        return $this->setData(self::REGISTRATION_ID, $registrationId);
    }

    /**
     * @inheritdoc
     */
    public function getMeetId()
    {
        return $this->getData(self::MEET_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMeetId($meetId)
    {
        return $this->setData(self::MEET_ID, $meetId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getPhoneNumber()
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * @inheritdoc
     */
    public function getRegistrationDate()
    {
        return $this->getData(self::REGISTRATION_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setRegistrationDate($registrationDate)
    {
        return $this->setData(self::REGISTRATION_DATE, $registrationDate);
    }

    /**
     * @inheritdoc
     */
    public function getAttendeeCount()
    {
        $value = $this->getData(self::ATTENDEE_COUNT);
        return $value !== null ? (int) $value : 1;
    }

    /**
     * @inheritdoc
     */
    public function setAttendeeCount($attendeeCount)
    {
        return $this->setData(self::ATTENDEE_COUNT, (int) $attendeeCount);
    }

    /**
     * @inheritdoc
     */
    public function getEmailRemindersDisabled()
    {
        return (bool) $this->getData(self::EMAIL_REMINDERS_DISABLED);
    }

    /**
     * @inheritdoc
     */
    public function setEmailRemindersDisabled($disabled)
    {
        return $this->setData(self::EMAIL_REMINDERS_DISABLED, $disabled ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getUnsubscribeCode()
    {
        return $this->getData(self::UNSUBSCRIBE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setUnsubscribeCode($code)
    {
        return $this->setData(self::UNSUBSCRIBE_CODE, $code);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Attendance Count
     *
     * @return int
     */
    public function getAttendanceCount()
    {
        return (int) $this->getData('attendance_count');
    }

    /**
     * Set Attendance Count
     *
     * @param int $attendanceCount
     * @return $this
     */
    public function setAttendanceCount($attendanceCount)
    {
        return $this->setData('attendance_count', (int) $attendanceCount);
    }
}

