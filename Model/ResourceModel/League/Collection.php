<?php
/**
 * Zacatrus Events League Collection
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\League;

use Zaca\Events\Model\League as LeagueModel;
use Zaca\Events\Model\ResourceModel\League as LeagueResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'league_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LeagueModel::class, LeagueResourceModel::class);
    }
}

