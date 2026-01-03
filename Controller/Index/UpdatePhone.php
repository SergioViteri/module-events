<?php
/**
 * Zacatrus Events Update Phone Number Controller
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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class UpdatePhone extends Action
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
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => __('You must be logged in to update your phone number.')
            ]);
        }

        // Get meetId and phoneNumber from POST data
        $postData = $this->getRequest()->getPostValue();
        $meetId = isset($postData['meetId']) ? (int) $postData['meetId'] : 0;
        $phoneNumber = isset($postData['phoneNumber']) ? trim($postData['phoneNumber']) : '';

        if (!$meetId) {
            return $result->setData([
                'success' => false,
                'message' => __('Meet ID is required.')
            ]);
        }

        // Validate phone number format: 9-15 digits, allows +, (, ), spaces, and dashes
        if (empty($phoneNumber)) {
            return $result->setData([
                'success' => false,
                'message' => __('Phone number is required.')
            ]);
        }

        // Validate phone number format: 9-15 digits, allows formatting characters (+, (, ), spaces, dashes)
        // Count only digits for validation
        $digitsOnly = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($digitsOnly) < 9 || strlen($digitsOnly) > 15) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid phone number format. Please enter 9-15 digits (formatting like +, (, ) is allowed).')
            ]);
        }

        // Store the phone number as entered (with formatting)
        // Only allow digits, +, (, ), spaces, and dashes for security
        $phoneNumber = preg_replace('/[^0-9+\-() ]/', '', $phoneNumber);

        try {
            $customerId = $this->customerSession->getCustomerId();
            
            // Get registration by meet and customer
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter('meet_id', $meetId)
                ->addFilter('customer_id', $customerId)
                ->create();
            
            $registrations = $this->registrationRepository->getList($searchCriteria);
            
            if ($registrations->getTotalCount() === 0) {
                return $result->setData([
                    'success' => false,
                    'message' => __('You are not registered for this meet.')
                ]);
            }

            $items = $registrations->getItems();
            $registration = reset($items);
            
            // Update phone number
            $registration->setPhoneNumber($phoneNumber);
            $this->registrationRepository->save($registration);

            return $result->setData([
                'success' => true,
                'message' => __('Phone number updated successfully.')
            ]);
        } catch (NoSuchEntityException $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Registration not found.')
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[UpdatePhone] Error updating phone number: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while updating your phone number.')
            ]);
        }
    }
}

