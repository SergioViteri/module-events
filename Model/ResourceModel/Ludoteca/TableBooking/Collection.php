<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaca\Events\Model\Ludoteca\TableBooking as TableBookingModel;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking as TableBookingResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'booking_id';

    protected function _construct()
    {
        $this->_init(TableBookingModel::class, TableBookingResource::class);
    }
}
