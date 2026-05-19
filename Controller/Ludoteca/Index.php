<?php
/**
 * Ludoteca landing — lists active stores and links to each store's booking page.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
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
            throw new NotFoundException(__('Page not found.'));
        }
        return $this->pageFactory->create();
    }
}
