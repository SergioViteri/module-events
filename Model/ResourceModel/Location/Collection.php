<?php
/**
 * Zacatrus Events Location Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Location;

use Zaca\Events\Model\Location as LocationModel;
use Zaca\Events\Model\ResourceModel\Location as LocationResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'location_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LocationModel::class, LocationResourceModel::class);
    }
}

