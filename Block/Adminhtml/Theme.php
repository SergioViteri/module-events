<?php
/**
 * Zacatrus Events Admin Theme Grid Container
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml;

class Theme extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_theme';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Themes');
        parent::_construct();
    }
}

