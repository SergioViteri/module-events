<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Model\AbstractModel;
use Zaca\Events\Api\Data\TimeSlotInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot as TimeSlotResource;

class TimeSlot extends AbstractModel implements TimeSlotInterface
{
    protected function _construct()
    {
        $this->_init(TimeSlotResource::class);
    }

    public function getTimeSlotId(): ?int
    {
        $value = $this->getData(self::TIME_SLOT_ID);
        return $value !== null ? (int) $value : null;
    }
    public function setTimeSlotId(int $id)
    {
        return $this->setData(self::TIME_SLOT_ID, $id);
    }

    public function getLocationId(): int
    {
        return (int) $this->getData(self::LOCATION_ID);
    }
    public function setLocationId(int $locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    public function getDayOfWeek(): ?int
    {
        $value = $this->getData(self::DAY_OF_WEEK);
        return $value === null || $value === '' ? null : (int) $value;
    }
    public function setDayOfWeek(?int $dayOfWeek)
    {
        return $this->setData(self::DAY_OF_WEEK, $dayOfWeek);
    }

    public function getStartTime(): string
    {
        return (string) $this->getData(self::START_TIME);
    }
    public function setStartTime(string $startTime)
    {
        return $this->setData(self::START_TIME, $startTime);
    }

    public function getEndTime(): string
    {
        return (string) $this->getData(self::END_TIME);
    }
    public function setEndTime(string $endTime)
    {
        return $this->setData(self::END_TIME, $endTime);
    }

    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }
    public function setSortOrder(int $sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }
    public function setIsActive(bool $isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }
}
