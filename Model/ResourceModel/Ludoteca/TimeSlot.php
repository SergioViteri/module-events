<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TimeSlot extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('zaca_events_time_slot', 'time_slot_id');
    }
}
