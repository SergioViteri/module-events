<?php
/**
 * Ludoteca landing — lists active stores and links to each store's booking page.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\PageFactory;
use Zaca\Events\Helper\Data as EventsHelper;

class Index extends Action
{
    private PageFactory $pageFactory;
    private EventsHelper $helper;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        EventsHelper $helper
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->helper = $helper;
    }

    public function execute()
    {
        if (!$this->helper->isLudotecaEnabled()) {
            return $this->buildNotFound();
        }
        return $this->pageFactory->create();
    }

    private function buildNotFound()
    {
        $this->getResponse()->setHttpResponseCode(404);
        return $this->pageFactory->create()->setStatusHeader(404, '1.1', 'Not Found');
    }
}
