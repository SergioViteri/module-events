<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaca\Events\Model\Ludoteca\TableBookingSlot as TableBookingSlotModel;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot as TableBookingSlotResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'slot_id';

    protected function _construct()
    {
        $this->_init(TableBookingSlotModel::class, TableBookingSlotResource::class);
    }
}
