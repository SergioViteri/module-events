<?php
/**
 * Zacatrus Events Store Collection
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Store;

use Zaca\Events\Model\Store as StoreModel;
use Zaca\Events\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'store_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(StoreModel::class, StoreResourceModel::class);
    }
}

