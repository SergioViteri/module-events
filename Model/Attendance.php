<?php
/**
 * Zacatrus Events Attendance Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\Attendance as AttendanceResourceModel;
use Magento\Framework\Model\AbstractModel;

class Attendance extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(AttendanceResourceModel::class);
    }

    /**
     * Get Attendance ID
     *
     * @return int|null
     */
    public function getAttendanceId()
    {
        return $this->getData('attendance_id');
    }

    /**
     * Set Attendance ID
     *
     * @param int $attendanceId
     * @return $this
     */
    public function setAttendanceId($attendanceId)
    {
        return $this->setData('attendance_id', $attendanceId);
    }

    /**
     * Get Registration ID
     *
     * @return int
     */
    public function getRegistrationId()
    {
        return (int) $this->getData('registration_id');
    }

    /**
     * Set Registration ID
     *
     * @param int $registrationId
     * @return $this
     */
    public function setRegistrationId($registrationId)
    {
        return $this->setData('registration_id', (int) $registrationId);
    }

    /**
     * Get Location ID
     *
     * @return int
     */
    public function getLocationId()
    {
        return (int) $this->getData('location_id');
    }

    /**
     * Set Location ID
     *
     * @param int $locationId
     * @return $this
     */
    public function setLocationId($locationId)
    {
        return $this->setData('location_id', (int) $locationId);
    }

    /**
     * Get Attendance Date
     *
     * @return string|null
     */
    public function getAttendanceDate()
    {
        return $this->getData('attendance_date');
    }

    /**
     * Set Attendance Date
     *
     * @param string $attendanceDate
     * @return $this
     */
    public function setAttendanceDate($attendanceDate)
    {
        return $this->setData('attendance_date', $attendanceDate);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }
}

