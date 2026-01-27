<?php
/**
 * Zacatrus Events Admin Participant Mass Confirm Attendance Controller
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

class MassConfirmAttendance extends Action
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
        $registrationIds = $this->getRequest()->getParam('registration_id');
        
        // Handle comma-separated string (Magento sometimes sends it this way)
        if (is_string($registrationIds) && !empty($registrationIds)) {
            $registrationIds = explode(',', $registrationIds);
            $registrationIds = array_map('trim', $registrationIds);
            $registrationIds = array_filter($registrationIds); // Remove empty values
        }
        
        if (!is_array($registrationIds) || empty($registrationIds)) {
            $this->messageManager->addError(__('Please select participant(s).'));
        } else {
            try {
            $count = 0;
            $errors = 0;
                
                foreach ($registrationIds as $registrationId) {
                    try {
                        $registration = $this->registrationFactory->create()->load($registrationId);
                        
                        if (!$registration->getId()) {
                            $errors++;
                            continue;
                        }

                        // Get meet to retrieve location_id
                        try {
                            $meet = $this->meetRepository->getById($registration->getMeetId());
                            
                            if (!$meet || !$meet->getLocationId()) {
                                $errors++;
                                continue;
                            }

                            // Record attendance
                            if ($this->attendanceValidator->recordAttendance($registrationId, $meet->getLocationId())) {
                                $count++;
                            } else {
                                $errors++;
                            }
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $errors++;
                        }
                    } catch (\Exception $e) {
                        $this->logger->error(
                            '[Mass Confirm Attendance] Error processing registration ID: ' . $registrationId . 
                            ' - ' . $e->getMessage()
                        );
                        $errors++;
                    }
                }

                if ($count > 0) {
                    $this->messageManager->addSuccess(
                        __('A total of %1 participant(s) have been confirmed for attendance.', $count)
                    );
                }
                
                if ($errors > 0) {
                    $this->messageManager->addWarning(
                        __('%1 participant(s) could not be confirmed. They may have already been confirmed for today.', $errors)
                    );
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred: %1', $e->getMessage()));
                $this->logger->error('[Mass Confirm Attendance] Error: ' . $e->getMessage());
            }
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
