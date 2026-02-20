<?php
/**
 * Zacatrus Events Meet Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api\Data;

interface MeetInterface
{
    const MEET_ID = 'meet_id';
    const NAME = 'name';
    const LOCATION_ID = 'location_id';
    const MEET_TYPE = 'meet_type';
    const THEME_ID = 'theme_id';
    const START_DATE = 'start_date';
    const DURATION_MINUTES = 'duration_minutes';
    const MAX_SLOTS = 'max_slots';
    const MAX_ATTENDEES_PER_REGISTRATION = 'max_attendees_per_registration';
    const DESCRIPTION = 'description';
    const REGISTRATION_CONDITIONS = 'registration_conditions';
    const INFO_URL_PATH = 'info_url_path';
    const RECURRENCE_TYPE = 'recurrence_type';
    const END_DATE = 'end_date';
    const IS_ACTIVE = 'is_active';
    const REMINDER_DAYS = 'reminder_days';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const MEET_TYPE_CASUAL = 'casual';
    const MEET_TYPE_LEAGUE = 'league';
    const MEET_TYPE_SPECIAL = 'special';

    const RECURRENCE_TYPE_NONE = 'none';
    const RECURRENCE_TYPE_QUINCENAL = 'quincenal';
    const RECURRENCE_TYPE_SEMANAL = 'semanal';

    /**
     * Get meet ID
     *
     * @return int|null
     */
    public function getMeetId();

    /**
     * Set meet ID
     *
     * @param int $meetId
     * @return $this
     */
    public function setMeetId($meetId);

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
     * Get location ID
     *
     * @return int
     */
    public function getLocationId();

    /**
     * Set location ID
     *
     * @param int $locationId
     * @return $this
     */
    public function setLocationId($locationId);

    /**
     * Get meet type
     *
     * @return string
     */
    public function getMeetType();

    /**
     * Set meet type
     *
     * @param string $meetType
     * @return $this
     */
    public function setMeetType($meetType);

    /**
     * Get theme ID
     *
     * @return int|null
     */
    public function getThemeId();

    /**
     * Set theme ID
     *
     * @param int|null $themeId
     * @return $this
     */
    public function setThemeId($themeId);

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
     * Get max attendees per registration (max people one customer can register per subscription)
     *
     * @return int
     */
    public function getMaxAttendeesPerRegistration();

    /**
     * Set max attendees per registration
     *
     * @param int $maxAttendeesPerRegistration
     * @return $this
     */
    public function setMaxAttendeesPerRegistration($maxAttendeesPerRegistration);

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
     * Get registration conditions (if set, user must accept before subscribing)
     *
     * @return string|null
     */
    public function getRegistrationConditions();

    /**
     * Set registration conditions
     *
     * @param string|null $registrationConditions
     * @return $this
     */
    public function setRegistrationConditions($registrationConditions);

    /**
     * Get info URL path
     *
     * @return string|null
     */
    public function getInfoUrlPath();

    /**
     * Set info URL path
     *
     * @param string|null $infoUrlPath
     * @return $this
     */
    public function setInfoUrlPath($infoUrlPath);

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
     * Get end date
     *
     * @return string|null
     */
    public function getEndDate();

    /**
     * Set end date
     *
     * @param string|null $endDate
     * @return $this
     */
    public function setEndDate($endDate);

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
     * Get reminder days
     *
     * @return string|null
     */
    public function getReminderDays();

    /**
     * Set reminder days
     *
     * @param string|null $reminderDays
     * @return $this
     */
    public function setReminderDays($reminderDays);

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

