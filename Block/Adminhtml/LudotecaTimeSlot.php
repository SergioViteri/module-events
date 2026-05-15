<?php

namespace Zaca\Events\Block\Adminhtml;

class LudotecaTimeSlot extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_ludotecatimeslot';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Ludoteca Time Slots');
        parent::_construct();
        $this->buttonList->update('add', 'label', __('Add Time Slot'));
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Zaca\Events\Block\Adminhtml\LudotecaTimeSlot\Grid::class,
                'ludotecatimeslot.grid'
            )
        );
        return parent::_prepareLayout();
    }
}
