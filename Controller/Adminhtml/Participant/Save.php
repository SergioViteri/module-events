<?php
/**
 * Zacatrus Events Admin Participant Save Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\RegistrationFactory;
use Zaca\Events\Helper\Email as EmailHelper;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param RegistrationFactory $registrationFactory
     * @param EmailHelper $emailHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        RegistrationFactory $registrationFactory,
        EmailHelper $emailHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->registrationFactory = $registrationFactory;
        $this->emailHelper = $emailHelper;
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
     * Save participant action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->registrationFactory->create();
            $id = $this->getRequest()->getParam('registration_id');
            $isNew = !$id;
            $oldStatus = null;

            if ($id) {
                $model->load($id);
                $oldStatus = $model->getStatus(); // Store old status before updating
            }

            $model->setData($data);
            $newStatus = $model->getStatus();

            try {
                $model->save();
                
                // Send registration email if this is a new registration
                if ($isNew) {
                    try {
                        $this->emailHelper->sendRegistrationEmail($model, true);
                    } catch (\Exception $e) {
                        // Log error but don't fail save
                        $this->logger->error('[Participant Save] Error sending registration email: ' . $e->getMessage());
                    }
                } elseif ($oldStatus === \Zaca\Events\Api\Data\RegistrationInterface::STATUS_WAITLIST 
                    && $newStatus === \Zaca\Events\Api\Data\RegistrationInterface::STATUS_CONFIRMED) {
                    // Send promotion email when status changes from waitlist to confirmed
                    try {
                        $this->emailHelper->sendWaitlistPromotionEmail($model);
                    } catch (\Exception $e) {
                        // Log error but don't fail save
                        $this->logger->error('[Participant Save] Error sending waitlist promotion email: ' . $e->getMessage());
                    }
                } elseif ($oldStatus === \Zaca\Events\Api\Data\RegistrationInterface::STATUS_CONFIRMED 
                    && $newStatus === \Zaca\Events\Api\Data\RegistrationInterface::STATUS_WAITLIST) {
                    // Send warning email when status changes from confirmed to waitlist
                    try {
                        $this->emailHelper->sendConfirmedToWaitlistEmail($model);
                    } catch (\Exception $e) {
                        // Log error but don't fail save
                        $this->logger->error('[Participant Save] Error sending confirmed to waitlist email: ' . $e->getMessage());
                    }
                }
                
                $this->messageManager->addSuccess(__('The participant has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['registration_id' => $model->getId(), '_current' => true]
                    );
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while saving the participant.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['registration_id' => $this->getRequest()->getParam('registration_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

