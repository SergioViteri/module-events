<?php
/**
 * Zacatrus Events Unregistration API Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class Unregister extends Action
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
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param MeetRepositoryInterface $meetRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        MeetRepositoryInterface $meetRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->meetRepository = $meetRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->logger = $logger;
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
                'message' => __('You must be logged in to unregister from meets.')
            ]);
        }

        $meetId = (int) $this->getRequest()->getParam('meetId');
        if (!$meetId) {
            return $result->setData([
                'success' => false,
                'message' => __('Meet ID is required.')
            ]);
        }

        try {
            $customerId = $this->customerSession->getCustomerId();
            $this->registrationRepository->unregisterCustomer($customerId, $meetId);
            
            // Calculate updated available slots
            $meet = $this->meetRepository->getById($meetId);
            $confirmed = $this->registrationRepository->getConfirmedAttendeeCountForMeet($meetId);
            $availableSlots = max(0, $meet->getMaxSlots() - $confirmed);
            
            return $result->setData([
                'success' => true,
                'message' => __('You have been successfully unregistered from this meet.'),
                'status' => null, // No registration status after unregistering
                'availableSlots' => $availableSlots,
                'maxSlots' => $meet->getMaxSlots()
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[Unregister] Error unregistering customer: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while processing your unregistration.')
            ]);
        }
    }
}

