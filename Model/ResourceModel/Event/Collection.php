<?php
/**
 * Zacatrus Events Event Collection
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Event;

use Zaca\Events\Model\Event as EventModel;
use Zaca\Events\Model\ResourceModel\Event as EventResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'event_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(EventModel::class, EventResourceModel::class);
    }
}

