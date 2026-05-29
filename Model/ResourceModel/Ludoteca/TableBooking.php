<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TableBooking extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('zaca_events_table_booking', 'booking_id');
    }
}
