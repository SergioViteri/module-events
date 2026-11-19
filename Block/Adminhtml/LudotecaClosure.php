<?php

namespace Zaca\Events\Block\Adminhtml;

class LudotecaClosure extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_ludotecaclosure';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Ludoteca Closures');
        parent::_construct();
        $this->buttonList->update('add', 'label', __('Add Closure'));
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Zaca\Events\Block\Adminhtml\LudotecaClosure\Grid::class,
                'ludotecaclosure.grid'
            )
        );
        return parent::_prepareLayout();
    }
}
