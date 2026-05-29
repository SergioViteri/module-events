<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaTimeSlot;

use Magento\Framework\Registry;
use Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot\Edit as EditController;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    private Registry $registry;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'time_slot_id';
        $this->_blockGroup = 'Zaca_Events';
        $this->_controller = 'adminhtml_ludotecatimeslot';
        parent::_construct();
        $this->buttonList->update('save', 'label', __('Save'));
    }

    public function getHeaderText()
    {
        $model = $this->registry->registry(EditController::REGISTRY_KEY);
        return ($model && $model->getId())
            ? __("Edit Time Slot #%1", (int) $model->getId())
            : __('New Time Slot');
    }
}
