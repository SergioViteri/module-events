<?php
/**
 * Zacatrus Events Router Plugin
 * Routes configured path to events module
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Plugin\Router;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\Base as BaseRouter;
use Zaca\Events\Helper\Data as EventsHelper;
use Psr\Log\LoggerInterface;

class Base
{
    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param EventsHelper $eventsHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventsHelper $eventsHelper,
        LoggerInterface $logger
    ) {
        $this->eventsHelper = $eventsHelper;
        $this->logger = $logger;
    }

    /**
     * Intercept router match to handle configured route path
     *
     * @param BaseRouter $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function aroundMatch(
        BaseRouter $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        // Get configured route path
        $configuredPath = $this->eventsHelper->getRoutePath();
        
        // Get the path info from request
        $pathInfo = trim($request->getPathInfo(), '/');
        
        // If configured path is "events", let normal routing proceed
        if ($configuredPath === 'events') {
            return $proceed($request);
        }
        
        // Check if request is for old /events path - redirect to configured path
        if (strpos($pathInfo, 'events/') === 0 || $pathInfo === 'events') {
            // Extract the action path (everything after /events/)
            $actionPath = '';
            if ($pathInfo !== 'events') {
                $actionPath = substr($pathInfo, 7); // Remove 'events/' prefix
            }
            
            // Route to Redirect controller to handle the 301 redirect
            $request->setModuleName('events');
            $request->setRouteName('zaca_events');
            $request->setControllerName('index');
            $request->setActionName('redirect');
            
            // Store the action path in request params so Redirect controller can use it
            if (!empty($actionPath)) {
                $request->setParam('action_path', $actionPath);
            }
            
            // Let the standard router handle the rest (will route to Redirect controller)
            return $proceed($request);
        }
        
        // Check if request matches configured path
        if (strpos($pathInfo, $configuredPath . '/') === 0 || $pathInfo === $configuredPath) {
            // Extract the action path (everything after configured path)
            $actionPath = '';
            if ($pathInfo !== $configuredPath) {
                $actionPath = substr($pathInfo, strlen($configuredPath) + 1);
            }
            
            // Rewrite the request to use 'events' frontName
            $request->setModuleName('events');
            $request->setRouteName('zaca_events');
            
            // Parse action path to set controller and action
            if (!empty($actionPath)) {
                $pathParts = explode('/', $actionPath);
                if (isset($pathParts[0])) {
                    $request->setControllerName($pathParts[0]);
                }
                if (isset($pathParts[1])) {
                    $request->setActionName($pathParts[1]);
                }
            } else {
                // Default to index controller and index action
                $request->setControllerName('index');
                $request->setActionName('index');
            }
            
            // Let the standard router handle the rest
            return $proceed($request);
        }
        
        // Not our path, let normal routing proceed
        return $proceed($request);
    }
}
