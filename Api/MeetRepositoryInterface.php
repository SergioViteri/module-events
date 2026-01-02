<?php
/**
 * Zacatrus Events Meet Repository Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\MeetInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface MeetRepositoryInterface
{
    /**
     * Save meet
     *
     * @param MeetInterface $meet
     * @return MeetInterface
     * @throws CouldNotSaveException
     */
    public function save(MeetInterface $meet): MeetInterface;

    /**
     * Get meet by ID
     *
     * @param int $meetId
     * @return MeetInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $meetId): MeetInterface;

    /**
     * Get list of meets
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete meet
     *
     * @param MeetInterface $meet
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(MeetInterface $meet): bool;

    /**
     * Delete meet by ID
     *
     * @param int $meetId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $meetId): bool;
}

