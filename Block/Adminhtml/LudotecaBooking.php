<?php

namespace Zaca\Events\Block\Adminhtml;

class LudotecaBooking extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_ludotecabooking';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Ludoteca Bookings');
        parent::_construct();
        $this->buttonList->remove('add');
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Zaca\Events\Block\Adminhtml\LudotecaBooking\Grid::class,
                'ludotecabooking.grid'
            )
        );
        return parent::_prepareLayout();
    }
}
