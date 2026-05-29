<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaBooking;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Grid extends Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::table_bookings');
    }

    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
    }
}
