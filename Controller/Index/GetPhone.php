<?php
/**
 * Zacatrus Events Get Phone Number Controller
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
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class GetPhone extends Action
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
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
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
                'phoneNumber' => null
            ]);
        }

        $customerId = $this->customerSession->getCustomerId();
        $meetId = (int) $this->getRequest()->getParam('meetId');
        $excludeMeetId = (int) $this->getRequest()->getParam('excludeMeetId');

        try {
            if ($meetId > 0) {
                // Get phone number for specific meet
                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteria = $searchCriteriaBuilder
                    ->addFilter('meet_id', $meetId)
                    ->addFilter('customer_id', $customerId)
                    ->create();
                
                $registrations = $this->registrationRepository->getList($searchCriteria);
                
                if ($registrations->getTotalCount() > 0) {
                    $items = $registrations->getItems();
                    $registration = reset($items);
                    $phoneNumber = $registration->getPhoneNumber();
                    
                    return $result->setData([
                        'success' => true,
                        'phoneNumber' => $phoneNumber ?: null
                    ]);
                }
            } else {
                // Get most recent phone number from other registrations
                $phoneNumber = $this->registrationRepository->getMostRecentPhoneNumber($customerId, $excludeMeetId);
                
                return $result->setData([
                    'success' => true,
                    'phoneNumber' => $phoneNumber
                ]);
            }
        } catch (\Exception $e) {
            // Silent fail, return null
        }

        return $result->setData([
            'success' => true,
            'phoneNumber' => null
        ]);
    }
}

