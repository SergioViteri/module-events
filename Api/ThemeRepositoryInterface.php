<?php
/**
 * Zacatrus Events Theme Repository Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\ThemeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface ThemeRepositoryInterface
{
    /**
     * Save theme
     *
     * @param ThemeInterface $theme
     * @return ThemeInterface
     * @throws CouldNotSaveException
     */
    public function save(ThemeInterface $theme): ThemeInterface;

    /**
     * Get theme by ID
     *
     * @param int $themeId
     * @return ThemeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $themeId): ThemeInterface;

    /**
     * Get theme list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete theme
     *
     * @param ThemeInterface $theme
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ThemeInterface $theme): bool;

    /**
     * Delete theme by ID
     *
     * @param int $themeId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $themeId): bool;
}

