<?php
/**
 * Zacatrus Events Admin Meet Grid Container
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml;

class Meet extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_meet';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Meets');
        parent::_construct();
    }
}

