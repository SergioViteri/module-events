<?php
/**
 * Zacatrus Events Registration Repository Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Api\Data\RegistrationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface RegistrationRepositoryInterface
{
    /**
     * Save registration
     *
     * @param RegistrationInterface $registration
     * @return RegistrationInterface
     * @throws CouldNotSaveException
     */
    public function save(RegistrationInterface $registration): RegistrationInterface;

    /**
     * Get registration by ID
     *
     * @param int $registrationId
     * @return RegistrationInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $registrationId): RegistrationInterface;

    /**
     * Get list of registrations
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete registration
     *
     * @param RegistrationInterface $registration
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(RegistrationInterface $registration): bool;

    /**
     * Delete registration by ID
     *
     * @param int $registrationId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $registrationId): bool;

    /**
     * Register customer to event
     *
     * @param int $customerId
     * @param int $eventId
     * @return RegistrationInterface
     * @throws CouldNotSaveException
     */
    public function registerCustomer(int $customerId, int $eventId): RegistrationInterface;

    /**
     * Unregister customer from event
     *
     * @param int $customerId
     * @param int $eventId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function unregisterCustomer(int $customerId, int $eventId): bool;
}

