<?php
/**
 * AJAX endpoint: month calendar for a location.
 *
 * Params: location_id (int), year (int), month (int 1-12).
 * Response: { "ok": true, "month": "2026-05", "days": { "2026-05-15": "free", ... } }
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Service\Ludoteca\AvailabilityService;

class Calendar extends Action
{
    private JsonFactory $jsonFactory;
    private AvailabilityService $availability;
    private CustomerSession $customerSession;
    private EventsHelper $helper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        AvailabilityService $availability,
        CustomerSession $customerSession,
        EventsHelper $helper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->availability = $availability;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->helper->isLudotecaEnabled()) {
            return $result->setHttpResponseCode(404)->setData(['ok' => false, 'error' => 'disabled']);
        }

        $locationId = (int) $this->getRequest()->getParam('location_id');
        $year = (int) $this->getRequest()->getParam('year');
        $month = (int) $this->getRequest()->getParam('month');

        if ($locationId <= 0 || $year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_params']);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        try {
            $days = $this->availability->monthCalendar($locationId, $year, $month, $customerId);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(500)->setData(['ok' => false, 'error' => 'internal']);
        }

        return $result->setData([
            'ok' => true,
            'month' => sprintf('%04d-%02d', $year, $month),
            'days' => $days,
            'is_logged_in' => $this->customerSession->isLoggedIn(),
            'club_signup_url' => $this->helper->getClubSignupUrl(),
        ]);
    }
}
