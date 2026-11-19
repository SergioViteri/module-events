<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaClosure;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Grid extends Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::ludoteca_closures');
    }

    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
    }
}
