<?php
/**
 * 301-redirect from the literal /ludoteca/... path to the configured route path.
 * Mounted by Plugin/Router/Ludoteca when admin sets a non-default route_path.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Zaca\Events\Helper\Data as EventsHelper;

class Redirect extends Action
{
    private EventsHelper $helper;

    public function __construct(Context $context, EventsHelper $helper)
    {
        parent::__construct($context);
        $this->helper = $helper;
    }

    public function execute()
    {
        if (!$this->helper->isLudotecaEnabled()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $tail = trim((string) $this->getRequest()->getParam('action_path'), '/');
        $target = $this->helper->getLudotecaPublicUrl($tail);
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($target)->setHttpResponseCode(301);
        return $redirect;
    }
}
