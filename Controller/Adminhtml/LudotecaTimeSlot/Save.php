<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\Ludoteca\TimeSlotFactory;

class Save extends Action
{
    private Session $adminSession;
    private TimeSlotFactory $factory;

    public function __construct(
        Action\Context $context,
        Session $adminSession,
        TimeSlotFactory $factory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->factory = $factory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::time_slots');
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $model = $this->factory->create();
        $id = $this->getRequest()->getParam('time_slot_id');
        if ($id) {
            $model->load($id);
        }

        // Normalise: empty day_of_week → null
        if (isset($data['day_of_week']) && $data['day_of_week'] === '') {
            $data['day_of_week'] = null;
        }
        // Append seconds to HH:MM if needed
        foreach (['start_time', 'end_time'] as $field) {
            if (!empty($data[$field]) && preg_match('/^\d{2}:\d{2}$/', (string) $data[$field])) {
                $data[$field] .= ':00';
            }
        }
        $model->setData($data);

        try {
            $model->save();
            $this->messageManager->addSuccessMessage(__('Time slot saved.'));
            $this->adminSession->setFormData(false);
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['time_slot_id' => $model->getId(), '_current' => true]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error saving time slot: %1', $e->getMessage()));
        }

        $this->adminSession->setFormData($data);
        return $resultRedirect->setPath('*/*/edit', ['time_slot_id' => $id]);
    }
}
