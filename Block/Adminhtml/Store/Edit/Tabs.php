<?php
namespace Zaca\Events\Block\Adminhtml\Store\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs {
    protected function _construct() {
        parent::_construct();

        $this->setId('store_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Store Information'));
    }
}

