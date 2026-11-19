<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaClosure;

use Magento\Framework\Registry;
use Zaca\Events\Controller\Adminhtml\LudotecaClosure\Edit as EditController;

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
        $this->_objectId = 'closure_id';
        $this->_blockGroup = 'Zaca_Events';
        $this->_controller = 'adminhtml_ludotecaclosure';
        parent::_construct();
        $this->buttonList->update('save', 'label', __('Save'));
    }

    public function getHeaderText()
    {
        $model = $this->registry->registry(EditController::REGISTRY_KEY);
        return ($model && $model->getId())
            ? __("Edit Closure #%1", (int) $model->getId())
            : __('New Closure');
    }
}
