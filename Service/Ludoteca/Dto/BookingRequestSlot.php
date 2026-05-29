<?php

namespace Zaca\Events\Service\Ludoteca\Dto;

class BookingRequestSlot
{
    public function __construct(
        public int $timeSlotId,
        public int $tablesCount
    ) {
    }
}
