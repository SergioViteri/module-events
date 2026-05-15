<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaTimeSlot\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('ludotecatimeslot_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Time Slot Information'));
    }
}
