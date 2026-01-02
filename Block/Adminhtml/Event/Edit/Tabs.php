<?php
namespace Zacatrus\Events\Block\Adminhtml\Event\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs {
    protected function _construct() {
        parent::_construct();

        $this->setId('event_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Event Information'));
    }
}

