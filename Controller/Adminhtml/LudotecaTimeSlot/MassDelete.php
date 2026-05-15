<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Zaca\Events\Model\Ludoteca\TimeSlotFactory;

class MassDelete extends Action
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
        $ids = $this->getRequest()->getParam('time_slot_id');
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select time slots.'));
        } else {
            try {
                $count = 0;
                foreach ($ids as $id) {
                    $model = $this->factory->create()->load($id);
                    if ($model->getId()) {
                        $model->delete();
                        $count++;
                    }
                }
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $count));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
