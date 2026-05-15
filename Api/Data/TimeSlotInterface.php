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

    public function getTimeSlotId(): ?int;
    public function setTimeSlotId(int $id);

    public function getLocationId(): int;
    public function setLocationId(int $locationId);

    public function getDayOfWeek(): ?int;
    public function setDayOfWeek(?int $dayOfWeek);

    public function getStartTime(): string;
    public function setStartTime(string $startTime);

    public function getEndTime(): string;
    public function setEndTime(string $endTime);

    public function getSortOrder(): int;
    public function setSortOrder(int $sortOrder);

    public function getIsActive(): bool;
    public function setIsActive(bool $isActive);
}
