<?php
/**
 * Zacatrus Events EventType Repository Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api;

use Zaca\Events\Model\EventType;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface EventTypeRepositoryInterface
{
    /**
     * Save event type
     *
     * @param EventType $eventType
     * @return EventType
     * @throws CouldNotSaveException
     */
    public function save(EventType $eventType): EventType;

    /**
     * Get event type by ID
     *
     * @param int $eventTypeId
     * @return EventType
     * @throws NoSuchEntityException
     */
    public function getById(int $eventTypeId): EventType;

    /**
     * Get event type by code
     *
     * @param string $code
     * @return EventType
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): EventType;

    /**
     * Delete event type
     *
     * @param EventType $eventType
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(EventType $eventType): bool;

    /**
     * Delete event type by ID
     *
     * @param int $eventTypeId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $eventTypeId): bool;

    /**
     * Get all active event types
     *
     * @return \Zaca\Events\Model\ResourceModel\EventType\Collection
     */
    public function getActiveEventTypes();
}

