<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Zaca\Events\Model\Ludoteca\TimeSlotFactory;

class Edit extends Action
{
    public const REGISTRY_KEY = 'zaca_events_ludoteca_time_slot';

    private Registry $registry;
    private Session $adminSession;
    private TimeSlotFactory $factory;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        TimeSlotFactory $factory
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->adminSession = $adminSession;
        $this->factory = $factory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::time_slots');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('time_slot_id');
        $model = $this->factory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Time slot not found.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register(self::REGISTRY_KEY, $model);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::time_slots')
            ->addBreadcrumb(__('Ludoteca Time Slots'), __('Ludoteca Time Slots'));
        $resultPage->getConfig()->getTitle()->prepend(__('Ludoteca Time Slots'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Time Slot') : __('New Time Slot')
        );
        return $resultPage;
    }
}
