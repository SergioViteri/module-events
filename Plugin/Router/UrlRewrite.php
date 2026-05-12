<?php
/**
 * Bypass Magento URL Rewrite router for events paths.
 *
 * Without this, /<route>/<location-slug> can be transformed by URL Rewrite into an
 * arbitrary catalog/cms redirect when the slug fuzzy-matches another entry.
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Plugin\Router;

use Magento\Framework\App\RequestInterface;
use Magento\UrlRewrite\Controller\Router as UrlRewriteRouter;
use Zaca\Events\Helper\Data as EventsHelper;

class UrlRewrite
{
    private EventsHelper $eventsHelper;

    public function __construct(EventsHelper $eventsHelper)
    {
        $this->eventsHelper = $eventsHelper;
    }

    public function aroundMatch(
        UrlRewriteRouter $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        $configuredPath = $this->eventsHelper->getRoutePath();
        $pathInfo = trim((string) $request->getPathInfo(), '/');

        if ($pathInfo === $configuredPath || $pathInfo === 'events') {
            return null;
        }
        if (strpos($pathInfo, $configuredPath . '/') === 0
            || strpos($pathInfo, 'events/') === 0
        ) {
            return null;
        }

        return $proceed($request);
    }
}
