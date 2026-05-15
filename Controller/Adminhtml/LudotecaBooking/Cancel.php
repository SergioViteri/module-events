<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaBooking;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\LudotecaEmail;

class Cancel extends Action
{
    private TableBookingRepositoryInterface $bookings;
    private LudotecaEmail $email;

    public function __construct(
        Action\Context $context,
        TableBookingRepositoryInterface $bookings,
        LudotecaEmail $email
    ) {
        parent::__construct($context);
        $this->bookings = $bookings;
        $this->email = $email;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::table_bookings');
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('booking_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $booking = $this->bookings->getById($id);
            if ($booking->getStatus() === TableBookingInterface::STATUS_CANCELLED) {
                $this->messageManager->addNoticeMessage(__('This booking is already cancelled.'));
            } else {
                $booking->setStatus(TableBookingInterface::STATUS_CANCELLED);
                $this->bookings->save($booking);
                $this->email->sendBookingCancellation($id);
                $this->messageManager->addSuccessMessage(__('Booking #%1 cancelled.', $id));
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Booking not found.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
