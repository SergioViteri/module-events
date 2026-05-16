<?php

namespace Zaca\Events\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Api\Data\TableBookingSlotInterface;

interface TableBookingSlotRepositoryInterface
{
    /**
     * Generic listing — accepts criteria such as booking_id IN (...) or
     * (location_id, booking_date) range filters.
     *
     * @return \Zaca\Events\Api\Data\TableBookingSlotSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @return \Zaca\Events\Api\Data\TableBookingSlotInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $slotId): TableBookingSlotInterface;
}
