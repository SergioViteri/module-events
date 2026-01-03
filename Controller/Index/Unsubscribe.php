<?php
/**
 * Zacatrus Events Unsubscribe Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Model\ResourceModel\Registration\CollectionFactory as RegistrationCollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

class Unsubscribe extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RegistrationCollectionFactory
     */
    protected $registrationCollectionFactory;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RegistrationCollectionFactory $registrationCollectionFactory
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RegistrationCollectionFactory $registrationCollectionFactory,
        RegistrationRepositoryInterface $registrationRepository,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->registrationCollectionFactory = $registrationCollectionFactory;
        $this->registrationRepository = $registrationRepository;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $code = $this->getRequest()->getParam('code');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Unsubscribe from Reminders'));

        if (empty($code)) {
            $this->messageManager->addErrorMessage(__('Invalid unsubscribe code.'));
            return $resultPage;
        }

        try {
            // Find registration by unsubscribe code
            $collection = $this->registrationCollectionFactory->create();
            $collection->addFieldToFilter('unsubscribe_code', $code);

            if ($collection->getSize() === 0) {
                $this->messageManager->addErrorMessage(__('Invalid unsubscribe code.'));
                $this->logger->warning('[Events Unsubscribe] Invalid unsubscribe code attempted: ' . $code);
                return $resultPage;
            }

            $registration = $collection->getFirstItem();

            // Disable email reminders
            $registration->setEmailRemindersDisabled(true);
            $this->registrationRepository->save($registration);

            $this->messageManager->addSuccessMessage(
                __('You have been unsubscribed from reminder emails for this event.')
            );

            $this->logger->info(
                '[Events Unsubscribe] User unsubscribed from reminders. Registration ID: ' . 
                $registration->getRegistrationId()
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while processing your request.'));
            $this->logger->error('[Events Unsubscribe] Error: ' . $e->getMessage());
        }

        return $resultPage;
    }
}

