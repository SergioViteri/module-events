<?php
/**
 * Zacatrus Events Admin Participant Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Zaca\Events\Model\RegistrationFactory;
use Zaca\Events\Helper\Email as EmailHelper;
use Zaca\Events\Api\MeetRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class Delete extends Action
{
    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param RegistrationFactory $registrationFactory
     * @param EmailHelper $emailHelper
     * @param MeetRepositoryInterface $meetRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        RegistrationFactory $registrationFactory,
        EmailHelper $emailHelper,
        MeetRepositoryInterface $meetRepository,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->registrationFactory = $registrationFactory;
        $this->emailHelper = $emailHelper;
        $this->meetRepository = $meetRepository;
        $this->customerRepository = $customerRepository;
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
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('registration_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->registrationFactory->create();
                $model->load($id);
                
                // Get data before deletion for email
                $meetId = $model->getMeetId();
                $customerId = $model->getCustomerId();
                $meet = null;
                $customerEmail = '';
                $customerName = '';
                
                try {
                    $meet = $this->meetRepository->getById($meetId);
                    $customer = $this->customerRepository->getById($customerId);
                    $customerEmail = $customer->getEmail();
                    $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
                } catch (\Exception $e) {
                    $this->logger->warning('[Participant Delete] Could not load meet/customer for email: ' . $e->getMessage());
                }
                
                $model->delete();
                
                // Send unregistration email (admin initiated)
                if ($meet && $customerEmail && $customerName) {
                    try {
                        $this->emailHelper->sendUnregistrationEmail($model, $meet, $customerEmail, $customerName, true);
                    } catch (\Exception $e) {
                        // Log error but don't fail deletion
                        $this->logger->error('[Participant Delete] Error sending unregistration email: ' . $e->getMessage());
                    }
                }
                
                $this->messageManager->addSuccess(__('The participant has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a participant to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

