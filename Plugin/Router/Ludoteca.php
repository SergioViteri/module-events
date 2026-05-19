<?php
/**
 * Maps the configured ludoteca path (default: 'ludoteca') to controller name 'ludoteca'.
 *
 * This module hosts two front-names: 'events' (Controller/Index/...) and
 * 'ludoteca' (Controller/Ludoteca/...). Without an explicit rewrite, both
 * front-names would resolve /<frontname>/index/index to the same file.
 * This plugin always rewrites the ludoteca request to controllerName='ludoteca'
 * to keep the two flows in separate directories.
 *
 * If admin sets zaca_events/ludoteca/route_path = 'ludotecas', incoming
 * /ludotecas/... is rewritten to /ludoteca/ludoteca/<action>, while the literal
 * /ludoteca/... is routed to a Redirect action that 301s to the configured path.
 */

namespace Zaca\Events\Plugin\Router;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\Base as BaseRouter;
use Zaca\Events\Helper\Data as EventsHelper;

class Ludoteca
{
    private EventsHelper $helper;

    public function __construct(EventsHelper $helper)
    {
        $this->helper = $helper;
    }

    public function aroundMatch(BaseRouter $subject, callable $proceed, RequestInterface $request)
    {
        // If the request already has a controller set (e.g. it was forwarded
        // after a NotFoundException from one of our controllers, or a later
        // router/handler already rewrote it), don't rewrite again — otherwise
        // we loop forever.
        if ($request->getControllerName() !== null) {
            return $proceed($request);
        }

        $configured = $this->helper->getLudotecaRoutePath();
        $pathInfo = trim((string) $request->getPathInfo(), '/');

        $isLudotecaPath = ($pathInfo === 'ludoteca' || strpos($pathInfo, 'ludoteca/') === 0
            || $pathInfo === $configured || strpos($pathInfo, $configured . '/') === 0);

        // Feature disabled → bypass the noroute handler (Amasty xsearch hijacks
        // it to redirect to /catalogsearch/result/) and render the CMS 404 page
        // directly. AJAX hits fall through to the real controllers, which
        // return a JSON 404 with error=disabled.
        if ($isLudotecaPath
            && !$this->helper->isLudotecaEnabled()
            && !$request->isAjax()
        ) {
            $request->setModuleName('cms')
                ->setControllerName('noroute')
                ->setActionName('index');
            return $proceed($request);
        }

        // Literal /ludoteca/... when admin configured a different path → 301 redirect
        if ($configured !== 'ludoteca'
            && ($pathInfo === 'ludoteca' || strpos($pathInfo, 'ludoteca/') === 0)
        ) {
            $tail = $pathInfo === 'ludoteca' ? '' : substr($pathInfo, strlen('ludoteca/'));
            $request->setModuleName('ludoteca');
            $request->setRouteName('zaca_ludoteca');
            $request->setControllerName('ludoteca');
            $request->setActionName('redirect');
            if ($tail !== '') {
                $request->setParam('action_path', $tail);
            }
            return $proceed($request);
        }

        // Configured path → rewrite to controller=ludoteca, action=<...>
        if ($pathInfo === $configured || strpos($pathInfo, $configured . '/') === 0) {
            $tail = $pathInfo === $configured ? '' : substr($pathInfo, strlen($configured) + 1);
            $tail = trim($tail, '/');

            $request->setModuleName('ludoteca');
            $request->setRouteName('zaca_ludoteca');
            $request->setControllerName('ludoteca');

            if ($tail === '') {
                // /<configured> → landing
                $request->setActionName('index');
            } else {
                $parts = explode('/', $tail);
                $first = $parts[0];

                // Reserved actions go through directly: /<configured>/<action>
                $reserved = [
                    'index', 'reserve', 'cancel', 'cancelmine', 'mybookings',
                    'qrcode', 'attendance', 'calendar', 'slots', 'redirect',
                ];
                if (in_array($first, $reserved, true)) {
                    $request->setActionName($first);
                } else {
                    // /<configured>/<store-slug>[/...] → store page filtered by slug
                    $request->setActionName('store');
                    $request->setParam('location_slug', $first);
                }
            }
            return $proceed($request);
        }

        return $proceed($request);
    }
}
