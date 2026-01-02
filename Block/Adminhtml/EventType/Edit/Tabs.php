<?php
/**
 * Zacatrus Events Admin EventType Edit Tabs
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\EventType\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('eventtype_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Event Type Information'));
    }
}

