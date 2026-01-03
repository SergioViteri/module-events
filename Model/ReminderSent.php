<?php
/**
 * Zacatrus Events Reminder Sent Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\ReminderSent as ReminderSentResourceModel;
use Magento\Framework\Model\AbstractModel;

class ReminderSent extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ReminderSentResourceModel::class);
    }

    /**
     * Get reminder sent ID
     *
     * @return int|null
     */
    public function getReminderSentId()
    {
        return $this->getData('reminder_sent_id');
    }

    /**
     * Set reminder sent ID
     *
     * @param int $reminderSentId
     * @return $this
     */
    public function setReminderSentId($reminderSentId)
    {
        return $this->setData('reminder_sent_id', $reminderSentId);
    }

    /**
     * Get registration ID
     *
     * @return int
     */
    public function getRegistrationId()
    {
        return $this->getData('registration_id');
    }

    /**
     * Set registration ID
     *
     * @param int $registrationId
     * @return $this
     */
    public function setRegistrationId($registrationId)
    {
        return $this->setData('registration_id', $registrationId);
    }

    /**
     * Get reminder days
     *
     * @return int
     */
    public function getReminderDays()
    {
        return $this->getData('reminder_days');
    }

    /**
     * Set reminder days
     *
     * @param int $reminderDays
     * @return $this
     */
    public function setReminderDays($reminderDays)
    {
        return $this->setData('reminder_days', $reminderDays);
    }

    /**
     * Get sent at
     *
     * @return string|null
     */
    public function getSentAt()
    {
        return $this->getData('sent_at');
    }

    /**
     * Set sent at
     *
     * @param string $sentAt
     * @return $this
     */
    public function setSentAt($sentAt)
    {
        return $this->setData('sent_at', $sentAt);
    }
}

