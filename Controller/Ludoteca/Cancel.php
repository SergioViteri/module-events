<?php
/**
 * Cancel a ludoteca booking via the unsubscribe code from the confirmation email.
 *
 * GET param: code
 */

namespace Zaca\Events\Controller\Ludoteca;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Helper\LudotecaEmail;

class Cancel extends Action
{
    private PageFactory $pageFactory;
    private TableBookingRepositoryInterface $bookings;
    private LudotecaEmail $email;
    private EventsHelper $helper;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        TableBookingRepositoryInterface $bookings,
        LudotecaEmail $email,
        EventsHelper $helper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->bookings = $bookings;
        $this->email = $email;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $code = trim((string) $this->getRequest()->getParam('code'));
        if ($code === '') {
            $this->messageManager->addErrorMessage(__('Falta el código de la reserva.'));
            return $this->redirectToLanding();
        }

        try {
            $booking = $this->bookings->getByUnsubscribeCode($code);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('No encontramos esa reserva. Puede que ya esté cancelada.'));
            return $this->redirectToLanding();
        }

        if ($booking->getStatus() === TableBookingInterface::STATUS_CANCELLED) {
            $this->messageManager->addNoticeMessage(__('Esta reserva ya estaba cancelada.'));
            return $this->redirectToLanding();
        }

        try {
            $booking->setStatus(TableBookingInterface::STATUS_CANCELLED);
            $this->bookings->save($booking);
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Cancel] Could not cancel booking ' . $booking->getBookingId() . ': ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('No se pudo cancelar la reserva. Inténtalo de nuevo.'));
            return $this->redirectToLanding();
        }

        try {
            $this->email->sendBookingCancellation($booking->getBookingId());
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Cancel] Cancellation email failed: ' . $e->getMessage());
        }

        $this->messageManager->addSuccessMessage(__('Tu reserva ha sido cancelada.'));
        return $this->redirectToLanding();
    }

    private function redirectToLanding()
    {
        return $this->resultRedirectFactory->create()
            ->setUrl($this->helper->getLudotecaPublicUrl());
    }
}
