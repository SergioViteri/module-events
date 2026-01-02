<?php
/**
 * Zacatrus Events Attendance Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Attendance;

use Zaca\Events\Model\Attendance as AttendanceModel;
use Zaca\Events\Model\ResourceModel\Attendance as AttendanceResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'attendance_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(AttendanceModel::class, AttendanceResourceModel::class);
    }
}

