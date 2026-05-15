<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaBooking;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    private PageFactory $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::table_bookings');
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Zaca_Events::table_bookings');
        $resultPage->getConfig()->getTitle()->prepend(__('Ludoteca Bookings'));
        return $resultPage;
    }
}
