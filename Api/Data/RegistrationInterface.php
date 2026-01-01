<?php
/**
 * Zacatrus Events Registration Interface
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Api\Data;

interface RegistrationInterface
{
    const REGISTRATION_ID = 'registration_id';
    const EVENT_ID = 'event_id';
    const CUSTOMER_ID = 'customer_id';
    const STATUS = 'status';
    const REGISTRATION_DATE = 'registration_date';
    const CREATED_AT = 'created_at';

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_WAITLIST = 'waitlist';

    /**
     * Get registration ID
     *
     * @return int|null
     */
    public function getRegistrationId();

    /**
     * Set registration ID
     *
     * @param int $registrationId
     * @return $this
     */
    public function setRegistrationId($registrationId);

    /**
     * Get event ID
     *
     * @return int
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
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get registration date
     *
     * @return string
     */
    public function getRegistrationDate();

    /**
     * Set registration date
     *
     * @param string $registrationDate
     * @return $this
     */
    public function setRegistrationDate($registrationDate);

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
}

