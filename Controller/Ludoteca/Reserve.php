<?php
/**
 * POST: create a ludoteca booking for the logged-in customer.
 *
 * Body params:
 *   location_id   (int)
 *   booking_date  (Y-m-d)
 *   phone_number  (string)
 *   slots         (array of { time_slot_id:int, tables_count:int })
 *   form_key      (Magento CSRF token)
 *
 * Returns JSON; the frontend handles the redirect to a confirmation view.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Helper\LudotecaEmail;
use Zaca\Events\Service\Ludoteca\Dto\BookingRequest;
use Zaca\Events\Service\Ludoteca\Dto\BookingRequestSlot;
use Zaca\Events\Service\Ludoteca\ReservationCreator;

class Reserve extends Action implements HttpPostActionInterface
{
    private JsonFactory $jsonFactory;
    private CustomerSession $customerSession;
    private EventsHelper $helper;
    private ReservationCreator $creator;
    private FormKeyValidator $formKeyValidator;
    private LudotecaEmail $email;
    private LoggerInterface $logger;
    private UrlInterface $urlBuilder;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CustomerSession $customerSession,
        EventsHelper $helper,
        ReservationCreator $creator,
        FormKeyValidator $formKeyValidator,
        LudotecaEmail $email,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->creator = $creator;
        $this->formKeyValidator = $formKeyValidator;
        $this->email = $email;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->helper->isLudotecaEnabled()) {
            return $result->setHttpResponseCode(404)->setData(['ok' => false, 'error' => 'disabled']);
        }
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'form_key']);
        }
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setHttpResponseCode(401)->setData([
                'ok' => false,
                'error' => 'login_required',
                'login_url' => $this->urlBuilder->getUrl('customer/account/login'),
            ]);
        }

        $locationId = (int) $this->getRequest()->getParam('location_id');
        $dateStr = (string) $this->getRequest()->getParam('booking_date');
        $phone = trim((string) $this->getRequest()->getParam('phone_number'));
        $rawSlots = $this->getRequest()->getParam('slots');

        if ($locationId <= 0
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)
            || $phone === ''
            || !is_array($rawSlots)
            || empty($rawSlots)
        ) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_params']);
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(400)->setData(['ok' => false, 'error' => 'bad_date']);
        }

        $slotDtos = [];
        foreach ($rawSlots as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slotDtos[] = new BookingRequestSlot(
                (int) ($row['time_slot_id'] ?? 0),
                (int) ($row['tables_count'] ?? 0)
            );
        }

        $request = new BookingRequest(
            $locationId,
            (int) $this->customerSession->getCustomerId(),
            $date,
            $phone,
            $slotDtos
        );

        try {
            $bookingId = $this->creator->create($request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $result->setHttpResponseCode(422)->setData([
                'ok' => false,
                'error' => 'validation',
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Reserve] Internal error: ' . $e->getMessage());
            return $result->setHttpResponseCode(500)->setData([
                'ok' => false,
                'error' => 'internal',
            ]);
        }

        try {
            $this->email->sendBookingConfirmation($bookingId);
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Reserve] Confirmation email failed: ' . $e->getMessage());
            // Do not fail the request — the booking succeeded.
        }

        return $result->setData([
            'ok' => true,
            'booking_id' => $bookingId,
            'message' => __('Tu reserva se ha creado correctamente. Te hemos enviado un email con el QR.')->render(),
        ]);
    }
}
