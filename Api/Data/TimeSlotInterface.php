<?php

namespace Zaca\Events\Api\Data;

interface TimeSlotInterface
{
    public const TIME_SLOT_ID = 'time_slot_id';
    public const LOCATION_ID = 'location_id';
    public const DAY_OF_WEEK = 'day_of_week';
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getTimeSlotId(): ?int;

    /**
     * @param int $id
     * @return $this
     */
    public function setTimeSlotId(int $id);

    /**
     * @return int
     */
    public function getLocationId(): int;

    /**
     * @param int $locationId
     * @return $this
     */
    public function setLocationId(int $locationId);

    /**
     * @return int|null
     */
    public function getDayOfWeek(): ?int;

    /**
     * @param int|null $dayOfWeek
     * @return $this
     */
    public function setDayOfWeek(?int $dayOfWeek);

    /**
     * @return string
     */
    public function getStartTime(): string;

    /**
     * @param string $startTime
     * @return $this
     */
    public function setStartTime(string $startTime);

    /**
     * @return string
     */
    public function getEndTime(): string;

    /**
     * @param string $endTime
     * @return $this
     */
    public function setEndTime(string $endTime);

    /**
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder);

    /**
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive);
}
