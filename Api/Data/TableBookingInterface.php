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

    public function getBookingId(): ?int;
    public function setBookingId(int $id);

    public function getLocationId(): int;
    public function setLocationId(int $locationId);

    public function getCustomerId(): int;
    public function setCustomerId(int $customerId);

    public function getBookingDate(): string;
    public function setBookingDate(string $bookingDate);

    public function getStatus(): string;
    public function setStatus(string $status);

    public function getPhoneNumber(): string;
    public function setPhoneNumber(string $phoneNumber);

    public function getUnsubscribeCode(): ?string;
    public function setUnsubscribeCode(?string $code);

    public function getCreatedAt(): ?string;
    public function getUpdatedAt(): ?string;
}
