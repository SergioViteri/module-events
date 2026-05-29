<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaBooking;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Zaca\Events\Api\TableBookingRepositoryInterface;

class View extends Action
{
    public const REGISTRY_KEY = 'zaca_events_ludoteca_booking';

    private Registry $registry;
    private TableBookingRepositoryInterface $bookings;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        TableBookingRepositoryInterface $bookings
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->bookings = $bookings;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::table_bookings');
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('booking_id');
        try {
            $booking = $this->bookings->getById($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Booking not found.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $this->registry->register(self::REGISTRY_KEY, $booking);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::table_bookings');
        $resultPage->getConfig()->getTitle()->prepend(__('Booking #%1', $booking->getBookingId()));
        return $resultPage;
    }
}
