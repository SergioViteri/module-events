<?php
/**
 * Zacatrus Events Admin Theme Edit Tabs
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Theme\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('theme_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Theme Information'));
    }
}

