<?php
/**
 * Zacatrus Events Store Repository Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\StoreInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface StoreRepositoryInterface
{
    /**
     * Save store
     *
     * @param StoreInterface $store
     * @return StoreInterface
     * @throws CouldNotSaveException
     */
    public function save(StoreInterface $store): StoreInterface;

    /**
     * Get store by ID
     *
     * @param int $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $storeId): StoreInterface;

    /**
     * Get list of stores
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete store
     *
     * @param StoreInterface $store
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(StoreInterface $store): bool;

    /**
     * Delete store by ID
     *
     * @param int $storeId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $storeId): bool;
}

