<?php
/**
 * Zacatrus Events Unregistration API Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Controller\Index;

use Zacatrus\Events\Api\RegistrationRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

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
     * @param Context $context
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
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
                'message' => __('You must be logged in to unregister from events.')
            ]);
        }

        $eventId = (int) $this->getRequest()->getParam('eventId');
        if (!$eventId) {
            return $result->setData([
                'success' => false,
                'message' => __('Event ID is required.')
            ]);
        }

        try {
            $customerId = $this->customerSession->getCustomerId();
            $this->registrationRepository->unregisterCustomer($customerId, $eventId);
            
            return $result->setData([
                'success' => true,
                'message' => __('You have been successfully unregistered from this event.')
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

