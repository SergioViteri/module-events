<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca\Closure;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaca\Events\Model\Ludoteca\Closure as ClosureModel;
use Zaca\Events\Model\ResourceModel\Ludoteca\Closure as ClosureResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'closure_id';

    protected function _construct()
    {
        $this->_init(ClosureModel::class, ClosureResource::class);
    }
}
