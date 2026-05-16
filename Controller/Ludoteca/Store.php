<?php
/**
 * Ludoteca per-store page: calendar + slot picker + booking form.
 *
 * Slug comes from the URL via the routing plugin: /<route_path>/<store-slug>.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\LocationFactory;

class Store extends Action
{
    private PageFactory $pageFactory;
    private EventsHelper $helper;
    private LocationFactory $locationFactory;
    private Registry $registry;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        EventsHelper $helper,
        LocationFactory $locationFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->helper = $helper;
        $this->locationFactory = $locationFactory;
        $this->registry = $registry;
    }

    public function execute()
    {
        if (!$this->helper->isLudotecaEnabled()) {
            $this->getResponse()->setHttpResponseCode(404);
            return $this->pageFactory->create()->setStatusHeader(404, '1.1', 'Not Found');
        }

        $slug = trim((string) $this->getRequest()->getParam('location_slug'));
        if ($slug === '') {
            return $this->redirectToLanding();
        }

        $location = $this->locationFactory->create();
        $location->load($slug, 'url_key');
        if (!$location->getId() || !$location->getIsActive()) {
            return $this->redirectToLanding();
        }

        $this->registry->register('current_ludoteca_location', $location, true);
        return $this->pageFactory->create();
    }

    private function redirectToLanding()
    {
        return $this->resultRedirectFactory->create()
            ->setUrl($this->helper->getLudotecaPublicUrl());
    }
}
