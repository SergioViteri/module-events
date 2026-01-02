<?php
/**
 * Zacatrus Events Admin EventType Grid Container
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml;

class EventType extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_eventtype';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Event Types');
        parent::_construct();
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Zaca\Events\Block\Adminhtml\EventType\Grid::class,
                'eventtype.grid'
            )
        );
        return parent::_prepareLayout();
    }
}

