<?php
/**
 * Serve a PNG QR code for a booking. The QR encodes the in-store check-in URL.
 *
 * Param: id (booking_id)
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Service\QrCodeGenerator;

class Qrcode extends Action
{
    private RawFactory $rawFactory;
    private TableBookingRepositoryInterface $bookings;
    private LocationFactory $locationFactory;
    private QrCodeGenerator $qr;
    private UrlInterface $urlBuilder;
    private EventsHelper $helper;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        RawFactory $rawFactory,
        TableBookingRepositoryInterface $bookings,
        LocationFactory $locationFactory,
        QrCodeGenerator $qr,
        UrlInterface $urlBuilder,
        EventsHelper $helper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->rawFactory = $rawFactory;
        $this->bookings = $bookings;
        $this->locationFactory = $locationFactory;
        $this->qr = $qr;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $bookingId = (int) $this->getRequest()->getParam('id');
        if ($bookingId <= 0) {
            return $this->redirectToLanding();
        }

        try {
            $booking = $this->bookings->getById($bookingId);
        } catch (NoSuchEntityException $e) {
            return $this->redirectToLanding();
        }

        $location = $this->locationFactory->create();
        $location->load($booking->getLocationId());
        if (!$location->getId() || !$location->getCode()) {
            return $this->redirectToLanding();
        }

        $base = rtrim($this->urlBuilder->getBaseUrl(['_secure' => true]), '/');
        $attendanceUrl = $base . $this->helper->getLudotecaPublicUrl('attendance', [
            'id' => $bookingId,
            'code' => $location->getCode(),
        ]);

        $binary = $this->qr->generateQrCodeBinary($attendanceUrl, 300);
        if ($binary === '') {
            return $this->redirectToLanding();
        }

        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'image/png');
        $result->setHeader('Content-Disposition', 'inline; filename="ludoteca-qr.png"');
        $result->setHeader('Cache-Control', 'public, max-age=3600');
        $result->setContents($binary);
        return $result;
    }

    private function redirectToLanding()
    {
        return $this->resultRedirectFactory->create()
            ->setUrl($this->helper->getLudotecaPublicUrl());
    }
}
