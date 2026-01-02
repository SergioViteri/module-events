<?php
/**
 * Zacatrus Events Meet Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Meet;

use Zaca\Events\Model\Meet as MeetModel;
use Zaca\Events\Model\ResourceModel\Meet as MeetResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'meet_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(MeetModel::class, MeetResourceModel::class);
    }
}

