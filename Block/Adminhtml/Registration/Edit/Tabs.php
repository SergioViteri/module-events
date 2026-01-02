<?php
namespace Zaca\Events\Block\Adminhtml\Registration\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs {
    protected function _construct() {
        parent::_construct();

        $this->setId('registration_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Registration Information'));
    }
}

