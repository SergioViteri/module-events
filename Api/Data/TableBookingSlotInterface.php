<?php

namespace Zaca\Events\Api\Data;

interface TableBookingSlotInterface
{
    public const SLOT_ID = 'slot_id';
    public const BOOKING_ID = 'booking_id';
    public const LOCATION_ID = 'location_id';
    public const BOOKING_DATE = 'booking_date';
    public const TIME_SLOT_ID = 'time_slot_id';
    public const TABLES_COUNT = 'tables_count';
    public const CREATED_AT = 'created_at';

    public function getSlotId(): ?int;
    public function setSlotId(int $id);

    public function getBookingId(): int;
    public function setBookingId(int $bookingId);

    public function getLocationId(): int;
    public function setLocationId(int $locationId);

    public function getBookingDate(): string;
    public function setBookingDate(string $bookingDate);

    public function getTimeSlotId(): int;
    public function setTimeSlotId(int $timeSlotId);

    public function getTablesCount(): int;
    public function setTablesCount(int $tablesCount);
}
