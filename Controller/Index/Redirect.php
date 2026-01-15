<?php
/**
 * Zacatrus Events Redirect Controller
 * Handles 301 redirects from old /events path to configured route path
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Zaca\Events\Helper\Data as EventsHelper;

class Redirect extends Action
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        EventsHelper $eventsHelper
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Execute action - redirect from /events to configured route path
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        // Get configured route path
        $configuredPath = $this->eventsHelper->getRoutePath();
        
        // If configured path is still "events", no redirect needed (shouldn't reach here)
        // But handle it gracefully
        if ($configuredPath === 'events') {
            // Redirect to index action of events (normal flow)
            return $resultRedirect->setPath($configuredPath);
        }
        
        // Get the action path from request params (set by router plugin)
        $request = $this->getRequest();
        $actionPath = $request->getParam('action_path', '');
        
        // Build redirect URL with configured path
        $redirectPath = $configuredPath;
        if (!empty($actionPath)) {
            $redirectPath .= '/' . $actionPath;
        }
        
        // Preserve query parameters
        $queryParams = $request->getQuery()->toArray();
        
        // Set 301 permanent redirect
        $resultRedirect->setHttpResponseCode(301);
        $resultRedirect->setPath($redirectPath, ['_query' => $queryParams]);
        
        return $resultRedirect;
    }
}
