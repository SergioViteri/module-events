<?php
/**
 * AJAX endpoint: time-slot availability for a location on a single date.
 *
 * Params: location_id (int), date (Y-m-d).
 * Response: { "ok": true, "slots": [ {time_slot_id, start_time, end_time, free_tables, total_tables, state}, ... ] }
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Service\Ludoteca\AvailabilityService;

class Slots extends Action
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
        $dateStr = (string) $this->getRequest()->getParam('date');
        if ($locationId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_params']);
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_date']);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        try {
            $slots = $this->availability->availabilityForDate($locationId, $date, $customerId);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(500)->setData(['ok' => false, 'error' => 'internal']);
        }

        return $result->setData([
            'ok' => true,
            'date' => $dateStr,
            'slots' => $slots,
        ]);
    }
}
