<?php
/**
 * Zacatrus Events Reminder Sent Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\ReminderSent;

use Zaca\Events\Model\ReminderSent as ReminderSentModel;
use Zaca\Events\Model\ResourceModel\ReminderSent as ReminderSentResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ReminderSentModel::class, ReminderSentResourceModel::class);
    }
}

