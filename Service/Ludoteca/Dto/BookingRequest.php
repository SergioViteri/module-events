<?php
/**
 * Input value object for ReservationCreator::create().
 */

namespace Zaca\Events\Service\Ludoteca\Dto;

class BookingRequest
{
    /** @var array<int, BookingRequestSlot> */
    public array $slots;

    public function __construct(
        public int $locationId,
        public int $customerId,
        public \DateTimeImmutable $bookingDate,
        public string $phoneNumber,
        array $slots
    ) {
        $this->slots = $slots;
    }
}
