<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaClosure;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Zaca\Events\Model\Ludoteca\ClosureFactory;

class MassDelete extends Action
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
        $ids = $this->getRequest()->getParam('closure_id');
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select closures.'));
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
