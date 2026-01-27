<?php
/**
 * Zacatrus Events Admin Participant Confirm Attendance Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\RegistrationFactory;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Service\AttendanceValidator;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class ConfirmAttendance extends Action
{
    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

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
     * @param MeetRepositoryInterface $meetRepository
     * @param AttendanceValidator $attendanceValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RegistrationFactory $registrationFactory,
        MeetRepositoryInterface $meetRepository,
        AttendanceValidator $attendanceValidator,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->registrationFactory = $registrationFactory;
        $this->meetRepository = $meetRepository;
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

            // Get meet to retrieve location_id
            $meet = $this->meetRepository->getById($registration->getMeetId());
            
            if (!$meet || !$meet->getLocationId()) {
                $this->messageManager->addError(__('Cannot confirm attendance: meet or location not found.'));
                return $resultRedirect->setPath('*/*/edit', ['registration_id' => $registrationId]);
            }

            // Record attendance using AttendanceValidator
            if ($this->attendanceValidator->recordAttendance($registrationId, $meet->getLocationId())) {
                $this->messageManager->addSuccess(
                    __('Attendance confirmed successfully for participant #%1', $registrationId)
                );
            } else {
                $this->messageManager->addError(__('Failed to confirm attendance. It may have already been recorded for today.'));
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addError(__('Meet not found for this registration.'));
            $this->logger->error('[Confirm Attendance] Meet not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while confirming attendance: %1', $e->getMessage()));
            $this->logger->error('[Confirm Attendance] Error: ' . $e->getMessage());
        }

        // Redirect back to edit page
        return $resultRedirect->setPath('*/*/edit', ['registration_id' => $registrationId]);
    }
}
