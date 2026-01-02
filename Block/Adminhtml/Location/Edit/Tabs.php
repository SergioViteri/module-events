<?php
/**
 * Zacatrus Events Admin Location Edit Tabs
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Location\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('location_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Location Information'));
    }
}

