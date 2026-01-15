<?php
/**
 * Zacatrus Events Admin Participant Grid Container
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml;

class Participant extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_participant';
        $this->_blockGroup = 'Zaca_Events';
        $this->_headerText = __('Participants');
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
                'Zaca\Events\Block\Adminhtml\Participant\Grid',
                'participant.grid'
            )
        );
        
        // Call parent to set up the grid
        parent::_prepareLayout();
        
        // Remove the "Add New" button
        $this->buttonList->remove('add');
        
        // Add export button
        $this->addButton(
            'export',
            [
                'label' => __('Export to CSV'),
                'onclick' => "setLocation('" . $this->getUrl('*/*/exportCsv') . "')",
                'class' => 'add'
            ]
        );
        
        return $this;
    }
}

