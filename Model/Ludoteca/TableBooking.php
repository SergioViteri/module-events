<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Model\AbstractModel;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking as TableBookingResource;

class TableBooking extends AbstractModel implements TableBookingInterface
{
    protected function _construct()
    {
        $this->_init(TableBookingResource::class);
    }

    public function getBookingId(): ?int
    {
        $value = $this->getData(self::BOOKING_ID);
        return $value !== null ? (int) $value : null;
    }
    public function setBookingId(int $id)
    {
        return $this->setData(self::BOOKING_ID, $id);
    }

    public function getLocationId(): int
    {
        return (int) $this->getData(self::LOCATION_ID);
    }
    public function setLocationId(int $locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    public function getCustomerId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }
    public function setCustomerId(int $customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getBookingDate(): string
    {
        return (string) $this->getData(self::BOOKING_DATE);
    }
    public function setBookingDate(string $bookingDate)
    {
        return $this->setData(self::BOOKING_DATE, $bookingDate);
    }

    public function getStatus(): string
    {
        return (string) $this->getData(self::STATUS);
    }
    public function setStatus(string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getPhoneNumber(): string
    {
        return (string) $this->getData(self::PHONE_NUMBER);
    }
    public function setPhoneNumber(string $phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    public function getUnsubscribeCode(): ?string
    {
        $value = $this->getData(self::UNSUBSCRIBE_CODE);
        return $value !== null ? (string) $value : null;
    }
    public function setUnsubscribeCode(?string $code)
    {
        return $this->setData(self::UNSUBSCRIBE_CODE, $code);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string) $value : null;
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value !== null ? (string) $value : null;
    }
}
