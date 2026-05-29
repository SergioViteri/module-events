<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaca\Events\Model\Ludoteca\TimeSlot as TimeSlotModel;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot as TimeSlotResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'time_slot_id';

    protected function _construct()
    {
        $this->_init(TimeSlotModel::class, TimeSlotResource::class);
    }
}
