<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\App\Action;
use Zaca\Events\Model\Ludoteca\TimeSlotFactory;

class Delete extends Action
{
    private TimeSlotFactory $factory;

    public function __construct(Action\Context $context, TimeSlotFactory $factory)
    {
        parent::__construct($context);
        $this->factory = $factory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::time_slots');
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('time_slot_id');
        if ($id > 0) {
            try {
                $model = $this->factory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('Time slot deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
