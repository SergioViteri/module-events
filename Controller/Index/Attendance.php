<?php
/**
 * Zacatrus Events Attendance Check Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Service\AttendanceValidator;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Registry;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Zaca\Events\Helper\Data as EventsHelper;

class Attendance extends Action
{
    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var AttendanceValidator
     */
    protected $attendanceValidator;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param MeetRepositoryInterface $meetRepository
     * @param AttendanceValidator $attendanceValidator
     * @param PageFactory $resultPageFactory
     * @param SessionManager $session
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        MeetRepositoryInterface $meetRepository,
        AttendanceValidator $attendanceValidator,
        PageFactory $resultPageFactory,
        SessionManager $session,
        LoggerInterface $logger,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        EventsHelper $eventsHelper
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->meetRepository = $meetRepository;
        $this->attendanceValidator = $attendanceValidator;
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $session;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->customerRepository = $customerRepository;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $registrationId = (int) $this->getRequest()->getParam('registrationId');
        
        if (!$registrationId) {
            $this->messageManager->addError(__('Invalid registration ID.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        try {
            // Load registration
            $registration = $this->registrationRepository->getById($registrationId);
            
            if (!$registration || !$registration->getRegistrationId()) {
                throw new NoSuchEntityException(__('Registration not found.'));
            }
            
            // Load meet
            $meetId = $registration->getMeetId();
            if (!$meetId) {
                $this->logger->error('[Attendance] Registration has no meet_id: ' . $registrationId);
                throw new \Exception(__('Registration has no associated meet.'));
            }
            
            $meet = $this->meetRepository->getById($meetId);
            
            if (!$meet || !$meet->getMeetId()) {
                $this->logger->error('[Attendance] Meet not found for meet_id: ' . $meetId);
                throw new NoSuchEntityException(__('Meet not found.'));
            }
            
            // Load customer data
            $customerName = '';
            try {
                $customer = $this->customerRepository->getById($registration->getCustomerId());
                $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
            } catch (\Exception $e) {
                $this->logger->error('[Attendance] Error loading customer: ' . $e->getMessage());
            }
            
            // Check if this is a POST request (location code submission or attendance check)
            if ($this->getRequest()->isPost()) {
                return $this->processPostRequest($registration, $meet, $customerName);
            }

            // GET request - show form or result
            return $this->processGetRequest($registration, $meet, $customerName);
            
        } catch (NoSuchEntityException $e) {
            $this->logger->error('[Attendance] NoSuchEntityException: ' . $e->getMessage());
            $this->messageManager->addError(__('Registration or meet not found.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        } catch (\Exception $e) {
            $this->logger->error('[Attendance] Exception: ' . $e->getMessage());
            $this->logger->error('[Attendance] File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[Attendance] Stack trace: ' . $e->getTraceAsString());
            $this->messageManager->addError(__('An error occurred while processing attendance: %1', $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }
    }

    /**
     * Process GET request
     *
     * @param \Zaca\Events\Api\Data\RegistrationInterface $registration
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param string $customerName
     * @return \Magento\Framework\View\Result\Page
     */
    protected function processGetRequest($registration, $meet, $customerName = '')
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Attendance Check'));

        // Disable browser caching
        $response = $this->getResponse();
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
        $response->setHeader('Pragma', 'no-cache', true);
        $response->setHeader('Expires', '0', true);

        // Start session if not already started
        if (!$this->session->isSessionExists()) {
            $this->session->start();
        }
        
        // Check if location code is in session and matches this meet's location
        $sessionLocationId = $this->session->getData('zaca_events_location_id');
        $sessionLocationCode = $this->session->getData('zaca_events_location_code');
        $meetLocationId = $meet->getLocationId();
        
        // Only consider location code valid if it matches the current meet's location
        $hasLocationCode = (bool) $sessionLocationCode && 
                          $sessionLocationId && 
                          (int) $sessionLocationId === (int) $meetLocationId;

        // If location ID is in session and matches, automatically validate and record attendance
        // But skip if we just validated in POST request (to avoid duplicate error messages)
        $alreadyValidated = $this->session->getData('zaca_events_attendance_validated');
        if ($sessionLocationId && (int) $sessionLocationId === (int) $meetLocationId && !$alreadyValidated) {
            try {
                $updatedRegistration = $this->validateAndRecordAttendance($registration, $meet, $sessionLocationId);
                // Use updated registration if available
                if ($updatedRegistration) {
                    $registration = $updatedRegistration;
                }
            } catch (\Exception $e) {
                $this->logger->error('[Attendance] Error during automatic validation: ' . $e->getMessage());
                $this->logger->error('[Attendance] Stack trace: ' . $e->getTraceAsString());
                // Don't redirect, just show error message
                $this->messageManager->addError(__('An error occurred while validating attendance: %1', $e->getMessage()));
            }
        }
        
        // If attendance was validated in POST, reload registration to get updated count
        if ($alreadyValidated) {
            $registration = $this->registrationRepository->getById($registration->getRegistrationId());
            $this->session->unsetData('zaca_events_attendance_validated');
        }

        // Pass data to template via registry
        $this->registry->register('current_registration', $registration);
        $this->registry->register('current_meet', $meet);
        $this->registry->register('customer_name', $customerName);
        $this->registry->register('has_location_code', $hasLocationCode);
        $this->registry->register('session_location_id', $sessionLocationId);

        return $resultPage;
    }

    /**
     * Process POST request
     *
     * @param \Zaca\Events\Api\Data\RegistrationInterface $registration
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param string $customerName
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function processPostRequest($registration, $meet, $customerName = '')
    {
        $locationCode = $this->getRequest()->getParam('location_code');

        $meetLocationId = $meet->getLocationId();
        
        // If location code is submitted, validate and store in session
        if ($locationCode) {
            if ($this->attendanceValidator->validateLocationCode($locationCode, $meetLocationId)) {
                // Start session if not already started
                if (!$this->session->isSessionExists()) {
                    $this->session->start();
                }
                // Store in session
                $this->session->setData('zaca_events_location_id', $meetLocationId);
                $this->session->setData('zaca_events_location_code', $locationCode);
                $this->messageManager->addSuccess(__('Location code validated successfully.'));
            } else {
                $this->messageManager->addError(__('Invalid location code.'));
            }
        }
        
        // Check if we have a valid location ID in session (can happen in same request or from previous)
        $sessionLocationId = $this->session->getData('zaca_events_location_id');
        
        // Only validate and record if session location matches meet location
        if ($sessionLocationId && (int) $sessionLocationId === (int) $meetLocationId) {
            $updatedRegistration = $this->validateAndRecordAttendance($registration, $meet, $sessionLocationId);
            // Use updated registration if available
            if ($updatedRegistration) {
                $registration = $updatedRegistration;
            }
        }

        // Set flag to prevent duplicate validation in GET request after redirect
        if (!$this->session->isSessionExists()) {
            $this->session->start();
        }
        $this->session->setData('zaca_events_attendance_validated', true);

        $resultRedirect = $this->resultRedirectFactory->create();
        $routePath = $this->eventsHelper->getRoutePath();
        return $resultRedirect->setPath($routePath . '/index/attendance', ['registrationId' => $registration->getRegistrationId()]);
    }

    /**
     * Validate and record attendance
     *
     * @param \Zaca\Events\Api\Data\RegistrationInterface $registration
     * @param \Zaca\Events\Api\Data\MeetInterface $meet
     * @param int $locationId
     * @return \Zaca\Events\Api\Data\RegistrationInterface|null Returns updated registration or null on failure
     */
    protected function validateAndRecordAttendance($registration, $meet, $locationId)
    {
        // Validate location matches
        if ($meet->getLocationId() != $locationId) {
            $this->messageManager->addError(__('The registration is not for this location.'));
            return;
        }

        // Validate date (considering recurrence)
        $checkDate = new \DateTime();
        if (!$this->attendanceValidator->isDateValidForMeet($meet, $checkDate)) {
            $this->messageManager->addError(__('The current date does not match the event date.'));
            return;
        }

        // Check for duplicate attendance (same day)
        if ($this->attendanceValidator->hasAttendedToday($registration->getRegistrationId(), $checkDate)) {
            $this->messageManager->addError(__('Attendance has already been recorded for today.'));
            return;
        }

        // Record attendance
        if ($this->attendanceValidator->recordAttendance($registration->getRegistrationId(), $locationId)) {
            $this->messageManager->addSuccess(__('Attendance recorded successfully!'));
            // Return updated registration to caller
            return $this->registrationRepository->getById($registration->getRegistrationId());
        } else {
            $this->messageManager->addError(__('Failed to record attendance. Please try again.'));
            return null;
        }
    }
}

