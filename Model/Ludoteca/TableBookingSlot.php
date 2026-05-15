<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Model\AbstractModel;
use Zaca\Events\Api\Data\TableBookingSlotInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot as TableBookingSlotResource;

class TableBookingSlot extends AbstractModel implements TableBookingSlotInterface
{
    protected function _construct()
    {
        $this->_init(TableBookingSlotResource::class);
    }

    public function getSlotId(): ?int
    {
        $value = $this->getData(self::SLOT_ID);
        return $value !== null ? (int) $value : null;
    }
    public function setSlotId(int $id)
    {
        return $this->setData(self::SLOT_ID, $id);
    }

    public function getBookingId(): int
    {
        return (int) $this->getData(self::BOOKING_ID);
    }
    public function setBookingId(int $bookingId)
    {
        return $this->setData(self::BOOKING_ID, $bookingId);
    }

    public function getLocationId(): int
    {
        return (int) $this->getData(self::LOCATION_ID);
    }
    public function setLocationId(int $locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    public function getBookingDate(): string
    {
        return (string) $this->getData(self::BOOKING_DATE);
    }
    public function setBookingDate(string $bookingDate)
    {
        return $this->setData(self::BOOKING_DATE, $bookingDate);
    }

    public function getTimeSlotId(): int
    {
        return (int) $this->getData(self::TIME_SLOT_ID);
    }
    public function setTimeSlotId(int $timeSlotId)
    {
        return $this->setData(self::TIME_SLOT_ID, $timeSlotId);
    }

    public function getTablesCount(): int
    {
        return (int) $this->getData(self::TABLES_COUNT);
    }
    public function setTablesCount(int $tablesCount)
    {
        return $this->setData(self::TABLES_COUNT, $tablesCount);
    }
}
