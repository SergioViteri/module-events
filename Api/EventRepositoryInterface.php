<?php
/**
 * Zacatrus Events Event Repository Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Api;

use Zacatrus\Events\Api\Data\EventInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface EventRepositoryInterface
{
    /**
     * Save event
     *
     * @param EventInterface $event
     * @return EventInterface
     * @throws CouldNotSaveException
     */
    public function save(EventInterface $event): EventInterface;

    /**
     * Get event by ID
     *
     * @param int $eventId
     * @return EventInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $eventId): EventInterface;

    /**
     * Get list of events
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete event
     *
     * @param EventInterface $event
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(EventInterface $event): bool;

    /**
     * Delete event by ID
     *
     * @param int $eventId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $eventId): bool;
}

