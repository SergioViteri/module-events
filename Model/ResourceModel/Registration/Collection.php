<?php
/**
 * Zacatrus Events Registration Collection
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model\ResourceModel\Registration;

use Zacatrus\Events\Model\Registration as RegistrationModel;
use Zacatrus\Events\Model\ResourceModel\Registration as RegistrationResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'registration_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(RegistrationModel::class, RegistrationResourceModel::class);
    }
}

