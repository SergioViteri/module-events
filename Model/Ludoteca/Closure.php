<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Model\AbstractModel;
use Zaca\Events\Model\ResourceModel\Ludoteca\Closure as ClosureResource;

/**
 * A period (both dates inclusive) during which a ludoteca takes no bookings.
 * A NULL location_id closes every ludoteca.
 */
class Closure extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ClosureResource::class);
    }
}
