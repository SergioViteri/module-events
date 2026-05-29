<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;

class NewAction extends Action
{
    private ForwardFactory $forwardFactory;

    public function __construct(Action\Context $context, ForwardFactory $forwardFactory)
    {
        parent::__construct($context);
        $this->forwardFactory = $forwardFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::time_slots');
    }

    public function execute()
    {
        return $this->forwardFactory->create()->forward('edit');
    }
}
