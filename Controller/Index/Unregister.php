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
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param MeetRepositoryInterface $meetRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        MeetRepositoryInterface $meetRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->meetRepository = $meetRepository;
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
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $collection = $this->registrationRepository->getList(
                $searchCriteriaBuilder
                    ->addFilter('meet_id', $meetId)
                    ->addFilter('status', 'confirmed')
                    ->create()
            );
            $confirmed = $collection->getTotalCount();
            $availableSlots = max(0, $meet->getMaxSlots() - $confirmed);
            
            return $result->setData([
                'success' => true,
                'message' => __('You have been successfully unregistered from this meet.'),
                'availableSlots' => $availableSlots,
                'maxSlots' => $meet->getMaxSlots()
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while processing your unregistration.')
            ]);
        }
    }
}

