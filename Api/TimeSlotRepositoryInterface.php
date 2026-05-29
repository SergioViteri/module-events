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
     * @return \Zaca\Events\Model\Ludoteca\TimeSlot
     * @throws CouldNotSaveException
     */
    public function save(TimeSlot $timeSlot): TimeSlot;

    /**
     * @return \Zaca\Events\Model\Ludoteca\TimeSlot
     * @throws NoSuchEntityException
     */
    public function getById(int $id): TimeSlot;

    /**
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TimeSlot $timeSlot): bool;

    /**
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool;

    /**
     * Active time slots for a location, ordered by sort_order then start_time.
     *
     * @return \Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot\Collection
     */
    public function getActiveByLocation(int $locationId): Collection;
}
