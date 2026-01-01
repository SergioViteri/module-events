<?php
/**
 * Zacatrus Events Event Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Api\Data;

interface EventInterface
{
    const EVENT_ID = 'event_id';
    const NAME = 'name';
    const STORE_ID = 'store_id';
    const EVENT_TYPE = 'event_type';
    const START_DATE = 'start_date';
    const DURATION_MINUTES = 'duration_minutes';
    const MAX_SLOTS = 'max_slots';
    const DESCRIPTION = 'description';
    const RECURRENCE_TYPE = 'recurrence_type';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const EVENT_TYPE_CASUAL = 'casual';
    const EVENT_TYPE_LEAGUE = 'league';
    const EVENT_TYPE_SPECIAL = 'special';

    const RECURRENCE_TYPE_NONE = 'none';
    const RECURRENCE_TYPE_QUINCENAL = 'quincenal';

    /**
     * Get event ID
     *
     * @return int|null
     */
    public function getEventId();

    /**
     * Set event ID
     *
     * @param int $eventId
     * @return $this
     */
    public function setEventId($eventId);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get event type
     *
     * @return string
     */
    public function getEventType();

    /**
     * Set event type
     *
     * @param string $eventType
     * @return $this
     */
    public function setEventType($eventType);

    /**
     * Get start date
     *
     * @return string
     */
    public function getStartDate();

    /**
     * Set start date
     *
     * @param string $startDate
     * @return $this
     */
    public function setStartDate($startDate);

    /**
     * Get duration minutes
     *
     * @return int
     */
    public function getDurationMinutes();

    /**
     * Set duration minutes
     *
     * @param int $durationMinutes
     * @return $this
     */
    public function setDurationMinutes($durationMinutes);

    /**
     * Get max slots
     *
     * @return int
     */
    public function getMaxSlots();

    /**
     * Set max slots
     *
     * @param int $maxSlots
     * @return $this
     */
    public function setMaxSlots($maxSlots);

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get recurrence type
     *
     * @return string
     */
    public function getRecurrenceType();

    /**
     * Set recurrence type
     *
     * @param string $recurrenceType
     * @return $this
     */
    public function setRecurrenceType($recurrenceType);

    /**
     * Get is active
     *
     * @return bool
     */
    public function getIsActive();

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}

