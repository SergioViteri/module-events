<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaClosure;

use Magento\Backend\App\Action;
use Zaca\Events\Model\Ludoteca\ClosureFactory;

class Delete extends Action
{
    private ClosureFactory $factory;

    public function __construct(Action\Context $context, ClosureFactory $factory)
    {
        parent::__construct($context);
        $this->factory = $factory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::ludoteca_closures');
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('closure_id');
        if ($id > 0) {
            try {
                $model = $this->factory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('Closure deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
