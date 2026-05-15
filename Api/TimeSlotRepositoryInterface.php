<?php

namespace Zaca\Events\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Model\Ludoteca\TimeSlot;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot\Collection;

interface TimeSlotRepositoryInterface
{
    /**
     * @return \Zaca\Events\Api\Data\TimeSlotSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(TimeSlot $timeSlot): TimeSlot;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): TimeSlot;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(TimeSlot $timeSlot): bool;

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool;

    /**
     * Active time slots for a location, ordered by sort_order then start_time.
     */
    public function getActiveByLocation(int $locationId): Collection;
}
