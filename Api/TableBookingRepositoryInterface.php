<?php

namespace Zaca\Events\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Model\Ludoteca\TableBooking;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking\Collection;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot\Collection as SlotCollection;

interface TableBookingRepositoryInterface
{
    /**
     * @return \Zaca\Events\Api\Data\TableBookingSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(TableBooking $booking): TableBooking;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): TableBooking;

    /**
     * @throws NoSuchEntityException
     */
    public function getByUnsubscribeCode(string $code): TableBooking;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(TableBooking $booking): bool;

    /**
     * Bookings for a customer (most recent first).
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Booking slot lines for a given booking, ordered by time_slot start_time.
     */
    public function getSlots(int $bookingId): SlotCollection;
}
