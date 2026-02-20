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
     * Get total confirmed attendee count (sum of attendee_count) for a meet
     *
     * @param int $meetId
     * @return int
     */
    public function getConfirmedAttendeeCountForMeet(int $meetId): int;

    /**
     * Register customer to meet
     *
     * @param int $customerId
     * @param int $meetId
     * @param string|null $phoneNumber
     * @param int|null $attendeeCount
     * @return RegistrationInterface
     * @throws CouldNotSaveException
     */
    public function registerCustomer(int $customerId, int $meetId, ?string $phoneNumber = null, ?int $attendeeCount = null): RegistrationInterface;

    /**
     * Get most recent phone number from customer's registrations
     *
     * @param int $customerId
     * @param int|null $excludeMeetId
     * @return string|null
     */
    public function getMostRecentPhoneNumber(int $customerId, ?int $excludeMeetId = null): ?string;

    /**
     * Unregister customer from meet
     *
     * @param int $customerId
     * @param int $meetId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function unregisterCustomer(int $customerId, int $meetId): bool;
}

