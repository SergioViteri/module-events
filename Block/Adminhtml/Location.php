<?php
/**
 * Zacatrus Events Admin Location Grid Container
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml;

class Location extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_location';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Locations');
        parent::_construct();
    }
}

