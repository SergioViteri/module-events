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
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
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
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
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

        // Get meetId from POST data (sent by AJAX)
        $postData = $this->getRequest()->getPostValue();
        $meetId = isset($postData['meetId']) ? (int) $postData['meetId'] : 0;
        
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

        try {
            $customerId = $this->customerSession->getCustomerId();
            $registration = $this->registrationRepository->registerCustomer($customerId, $meetId);
            
            $message = $registration->getStatus() === 'waitlist' 
                ? __('You have been added to the waitlist.')
                : __('You have been successfully registered for this meet.');

            return $result->setData([
                'success' => true,
                'message' => $message,
                'status' => $registration->getStatus()
            ]);
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

