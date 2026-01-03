<?php
/**
 * Zacatrus Events Registration API Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Helper\Calendar;
use Zaca\Events\Model\LocationFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class Register extends Action
{
    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var Calendar
     */
    protected $calendarHelper;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param MeetRepositoryInterface $meetRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param Calendar $calendarHelper
     * @param LocationFactory $locationFactory
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        MeetRepositoryInterface $meetRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        Calendar $calendarHelper,
        LocationFactory $locationFactory
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->meetRepository = $meetRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->calendarHelper = $calendarHelper;
        $this->locationFactory = $locationFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        // Debug: Log that controller is reached
        $this->logger->info('Register controller execute() called');
        
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => __('You must be logged in to register for meets.')
            ]);
        }

        // Get meetId and phoneNumber from POST data (sent by AJAX)
        $postData = $this->getRequest()->getPostValue();
        $meetId = isset($postData['meetId']) ? (int) $postData['meetId'] : 0;
        $phoneNumber = isset($postData['phoneNumber']) ? trim($postData['phoneNumber']) : '';
        
        // Fallback to getParam if not in POST
        if (!$meetId) {
            $meetId = (int) $this->getRequest()->getParam('meetId');
        }
        
        if (!$meetId) {
            $this->logger->error('Registration failed: meetId not provided. POST data: ' . json_encode($postData));
            return $result->setData([
                'success' => false,
                'message' => __('Meet ID is required.')
            ]);
        }

        // Validate phone number format (9-15 digits, allows +, (, ), spaces, and dashes)
        if (empty($phoneNumber)) {
            return $result->setData([
                'success' => false,
                'requiresPhone' => true,
                'message' => __('Phone number is required for registration.')
            ]);
        }

        // Validate phone number format: 9-15 digits, allows formatting characters (+, (, ), spaces, dashes)
        // Count only digits for validation
        $digitsOnly = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($digitsOnly) < 9 || strlen($digitsOnly) > 15) {
            return $result->setData([
                'success' => false,
                'requiresPhone' => true,
                'message' => __('Invalid phone number format. Please enter 9-15 digits (formatting like +, (, ) is allowed).')
            ]);
        }

        // Store the phone number as entered (with formatting)
        // Only allow digits, +, (, ), spaces, and dashes for security
        $phoneNumber = preg_replace('/[^0-9+\-() ]/', '', $phoneNumber);

        try {
            $customerId = $this->customerSession->getCustomerId();
            $registration = $this->registrationRepository->registerCustomer($customerId, $meetId, $phoneNumber);
            
            // Calculate updated available slots
            $meet = $this->meetRepository->getById($meetId);
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $collection = $this->registrationRepository->getList(
                $searchCriteriaBuilder
                    ->addFilter('meet_id', $meetId)
                    ->addFilter('status', 'confirmed')
                    ->create()
            );
            $confirmed = $collection->getTotalCount();
            $availableSlots = max(0, $meet->getMaxSlots() - $confirmed);
            
            $message = $registration->getStatus() === 'waitlist' 
                ? __('You have been added to the waitlist.')
                : __('You have been successfully registered for this meet.');

            $responseData = [
                'success' => true,
                'message' => $message,
                'status' => $registration->getStatus(),
                'availableSlots' => $availableSlots,
                'maxSlots' => $meet->getMaxSlots()
            ];

            // Add calendar URLs if status is confirmed
            if ($registration->getStatus() === 'confirmed') {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                $responseData['calendarIcalUrl'] = $this->calendarHelper->getIcalUrl($meetId);
                $responseData['calendarGoogleUrl'] = $this->calendarHelper->getGoogleCalendarUrl($meet, $location);
            }

            return $result->setData($responseData);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            $this->logger->error('Registration error: ' . $errorMessage);
            $this->logger->error('File: ' . $errorFile . ' Line: ' . $errorLine);
            $this->logger->error('Stack trace: ' . $errorTrace);
            
            // Also write to a custom log file
            error_log("Registration Error: $errorMessage in $errorFile:$errorLine\n$errorTrace", 3, BP . '/var/log/events_registration.log');
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while processing your registration: %1', $errorMessage),
                'debug' => [
                    'message' => $errorMessage,
                    'file' => $errorFile,
                    'line' => $errorLine
                ]
            ]);
        }
    }
}

