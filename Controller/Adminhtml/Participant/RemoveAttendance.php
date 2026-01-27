<?php
/**
 * Zacatrus Events Admin Participant Remove Attendance Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\RegistrationFactory;
use Zaca\Events\Service\AttendanceValidator;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class RemoveAttendance extends Action
{
    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var AttendanceValidator
     */
    protected $attendanceValidator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param RegistrationFactory $registrationFactory
     * @param AttendanceValidator $attendanceValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RegistrationFactory $registrationFactory,
        AttendanceValidator $attendanceValidator,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->registrationFactory = $registrationFactory;
        $this->attendanceValidator = $attendanceValidator;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::participants');
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $registrationId = (int) $this->getRequest()->getParam('registration_id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$registrationId) {
            $this->messageManager->addError(__('Please select a participant.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            // Load registration
            $registration = $this->registrationFactory->create()->load($registrationId);
            
            if (!$registration->getId()) {
                $this->messageManager->addError(__('This participant no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            // Remove attendance using AttendanceValidator
            if ($this->attendanceValidator->removeAttendance($registrationId)) {
                $this->messageManager->addSuccess(
                    __('Attendance removed successfully for participant #%1', $registrationId)
                );
            } else {
                $this->messageManager->addError(__('Failed to remove attendance. No attendance records found.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while removing attendance: %1', $e->getMessage()));
            $this->logger->error('[Remove Attendance] Error: ' . $e->getMessage());
        }

        // Redirect back to edit page
        return $resultRedirect->setPath('*/*/edit', ['registration_id' => $registrationId]);
    }
}
