<?php
/**
 * Zacatrus Events League Repository Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\LeagueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface LeagueRepositoryInterface
{
    /**
     * Save league
     *
     * @param LeagueInterface $league
     * @return LeagueInterface
     * @throws CouldNotSaveException
     */
    public function save(LeagueInterface $league): LeagueInterface;

    /**
     * Get league by ID
     *
     * @param int $leagueId
     * @return LeagueInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $leagueId): LeagueInterface;

    /**
     * Get list of leagues
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete league
     *
     * @param LeagueInterface $league
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(LeagueInterface $league): bool;

    /**
     * Delete league by ID
     *
     * @param int $leagueId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $leagueId): bool;
}

