<?php
/**
 * Cancel a ludoteca booking from the customer's "my bookings" panel.
 *
 * Requires: logged-in customer + valid form_key. Validates that the booking
 * belongs to the current customer before cancelling. Sends the cancellation
 * email and redirects back to the ludoteca landing.
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Helper\LudotecaEmail;

class CancelMine extends Action implements HttpPostActionInterface
{
    private CustomerSession $customerSession;
    private TableBookingRepositoryInterface $bookings;
    private LudotecaEmail $email;
    private EventsHelper $helper;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        TableBookingRepositoryInterface $bookings,
        LudotecaEmail $email,
        EventsHelper $helper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->bookings = $bookings;
        $this->email = $email;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $referer = (string) $this->_redirect->getRefererUrl();
        $back = $referer !== '' ? $referer : $this->_url->getDirectUrl($this->helper->getLudotecaRoutePath());
        $redirect = $this->resultRedirectFactory->create()->setUrl($back);

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(__('Tienes que iniciar sesión para reservar.'));
            return $redirect;
        }

        $bookingId = (int) $this->getRequest()->getParam('booking_id');
        if ($bookingId <= 0) {
            $this->messageManager->addErrorMessage(__('Reserva no encontrada.'));
            return $redirect;
        }

        try {
            $booking = $this->bookings->getById($bookingId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Reserva no encontrada.'));
            return $redirect;
        }

        if ((int) $booking->getCustomerId() !== (int) $this->customerSession->getCustomerId()) {
            $this->messageManager->addErrorMessage(__('Reserva no encontrada.'));
            return $redirect;
        }

        if ($booking->getStatus() === TableBookingInterface::STATUS_CANCELLED) {
            $this->messageManager->addNoticeMessage(__('Esta reserva ya estaba cancelada.'));
            return $redirect;
        }

        try {
            $booking->setStatus(TableBookingInterface::STATUS_CANCELLED);
            $this->bookings->save($booking);
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca CancelMine] Could not cancel booking ' . $bookingId . ': ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('No se pudo cancelar la reserva. Inténtalo de nuevo.'));
            return $redirect;
        }

        try {
            $this->email->sendBookingCancellation($bookingId);
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca CancelMine] Cancellation email failed: ' . $e->getMessage());
        }

        $this->messageManager->addSuccessMessage(__('Tu reserva ha sido cancelada.'));
        return $redirect;
    }
}
