<?php
/**
 * Zacatrus Events Meet Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\Meet as MeetResourceModel;
use Zaca\Events\Api\Data\MeetInterface;
use Magento\Framework\Model\AbstractModel;

class Meet extends AbstractModel implements MeetInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(MeetResourceModel::class);
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
    public function getLocationId()
    {
        return $this->getData(self::LOCATION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLocationId($locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritdoc
     */
    public function getMeetType()
    {
        return $this->getData(self::MEET_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setMeetType($meetType)
    {
        return $this->setData(self::MEET_TYPE, $meetType);
    }

    /**
     * @inheritdoc
     */
    public function getThemeId()
    {
        return $this->getData(self::THEME_ID);
    }

    /**
     * @inheritdoc
     */
    public function setThemeId($themeId)
    {
        return $this->setData(self::THEME_ID, $themeId);
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
    public function getEndDate()
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setEndDate($endDate)
    {
        return $this->setData(self::END_DATE, $endDate);
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

