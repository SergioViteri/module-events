<?php
/**
 * Zacatrus Events EventType Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\EventType;

use Zaca\Events\Model\EventType as EventTypeModel;
use Zaca\Events\Model\ResourceModel\EventType as EventTypeResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'event_type_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(EventTypeModel::class, EventTypeResourceModel::class);
    }
}

