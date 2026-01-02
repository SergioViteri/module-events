<?php
/**
 * Zacatrus Events Theme Collection
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel\Theme;

use Zaca\Events\Model\Theme as ThemeModel;
use Zaca\Events\Model\ResourceModel\Theme as ThemeResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ThemeModel::class, ThemeResourceModel::class);
    }
}

