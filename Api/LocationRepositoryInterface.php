<?php
/**
 * Zacatrus Events Location Repository Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\LocationInterface;
use Zaca\Events\Api\Data\LocationSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface LocationRepositoryInterface
{
    /**
     * Save location
     *
     * @param LocationInterface $location
     * @return LocationInterface
     * @throws CouldNotSaveException
     */
    public function save(LocationInterface $location): LocationInterface;

    /**
     * Get location by ID
     *
     * @param int $locationId
     * @return LocationInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $locationId): LocationInterface;

    /**
     * Get list of locations
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return LocationSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete location
     *
     * @param LocationInterface $location
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(LocationInterface $location): bool;

    /**
     * Delete location by ID
     *
     * @param int $locationId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $locationId): bool;
}
