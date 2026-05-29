<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaBooking;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Zaca\Events\Api\Data\TableBookingInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\LudotecaEmail;

class MassCancel extends Action
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
        $ids = $this->getRequest()->getParam('booking_id');
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select bookings.'));
        } else {
            $count = 0;
            foreach ($ids as $id) {
                try {
                    $booking = $this->bookings->getById((int) $id);
                    if ($booking->getStatus() !== TableBookingInterface::STATUS_CANCELLED) {
                        $booking->setStatus(TableBookingInterface::STATUS_CANCELLED);
                        $this->bookings->save($booking);
                        $this->email->sendBookingCancellation((int) $id);
                        $count++;
                    }
                } catch (\Exception $e) {
                    // skip and continue
                }
            }
            $this->messageManager->addSuccessMessage(__('%1 booking(s) cancelled.', $count));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
