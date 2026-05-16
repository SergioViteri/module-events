<?php

namespace Zaca\Events\Api\Data;

interface TableBookingInterface
{
    public const BOOKING_ID = 'booking_id';
    public const LOCATION_ID = 'location_id';
    public const CUSTOMER_ID = 'customer_id';
    public const BOOKING_DATE = 'booking_date';
    public const STATUS = 'status';
    public const PHONE_NUMBER = 'phone_number';
    public const UNSUBSCRIBE_CODE = 'unsubscribe_code';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return int|null
     */
    public function getBookingId(): ?int;

    /**
     * @param int $id
     * @return $this
     */
    public function setBookingId(int $id);

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
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId);

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
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * @return string
     */
    public function getPhoneNumber(): string;

    /**
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(string $phoneNumber);

    /**
     * @return string|null
     */
    public function getUnsubscribeCode(): ?string;

    /**
     * @param string|null $code
     * @return $this
     */
    public function setUnsubscribeCode(?string $code);

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
