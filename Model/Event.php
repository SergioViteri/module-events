<?php
/**
 * Zacatrus Events Event Model
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\Event as EventResourceModel;
use Zaca\Events\Api\Data\EventInterface;
use Magento\Framework\Model\AbstractModel;

class Event extends AbstractModel implements EventInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(EventResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getEventId()
    {
        return $this->getData(self::EVENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEventId($eventId)
    {
        return $this->setData(self::EVENT_ID, $eventId);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getEventType()
    {
        return $this->getData(self::EVENT_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setEventType($eventType)
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
    }

    /**
     * @inheritdoc
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setStartDate($startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * @inheritdoc
     */
    public function getDurationMinutes()
    {
        return $this->getData(self::DURATION_MINUTES);
    }

    /**
     * @inheritdoc
     */
    public function setDurationMinutes($durationMinutes)
    {
        return $this->setData(self::DURATION_MINUTES, $durationMinutes);
    }

    /**
     * @inheritdoc
     */
    public function getMaxSlots()
    {
        return $this->getData(self::MAX_SLOTS);
    }

    /**
     * @inheritdoc
     */
    public function setMaxSlots($maxSlots)
    {
        return $this->setData(self::MAX_SLOTS, $maxSlots);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getRecurrenceType()
    {
        return $this->getData(self::RECURRENCE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setRecurrenceType($recurrenceType)
    {
        return $this->setData(self::RECURRENCE_TYPE, $recurrenceType);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
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
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

