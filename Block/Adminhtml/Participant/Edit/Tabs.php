<?php
/**
 * Zacatrus Events Admin Participant Edit Tabs
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Participant\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('participant_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Participant Information'));
    }
}

