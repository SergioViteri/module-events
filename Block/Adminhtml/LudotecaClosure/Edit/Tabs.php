<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaClosure\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('ludotecaclosure_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Closure Information'));
    }
}
