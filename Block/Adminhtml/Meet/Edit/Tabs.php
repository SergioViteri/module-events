<?php
/**
 * Zacatrus Events Admin Meet Edit Tabs
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Meet\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('meet_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Meet Information'));
    }
}

