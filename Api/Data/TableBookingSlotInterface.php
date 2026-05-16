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

    /**
     * @return int|null
     */
    public function getSlotId(): ?int;

    /**
     * @param int $id
     * @return $this
     */
    public function setSlotId(int $id);

    /**
     * @return int
     */
    public function getBookingId(): int;

    /**
     * @param int $bookingId
     * @return $this
     */
    public function setBookingId(int $bookingId);

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
     * @return string
     */
    public function getBookingDate(): string;

    /**
     * @param string $bookingDate
     * @return $this
     */
    public function setBookingDate(string $bookingDate);

    /**
     * @return int
     */
    public function getTimeSlotId(): int;

    /**
     * @param int $timeSlotId
     * @return $this
     */
    public function setTimeSlotId(int $timeSlotId);

    /**
     * @return int
     */
    public function getTablesCount(): int;

    /**
     * @param int $tablesCount
     * @return $this
     */
    public function setTablesCount(int $tablesCount);
}
