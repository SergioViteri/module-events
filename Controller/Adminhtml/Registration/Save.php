<?php
namespace Zaca\Events\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\RegistrationFactory;

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
     * @param Action\Context $context
     * @param Session $adminSession
     * @param RegistrationFactory $registrationFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        RegistrationFactory $registrationFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->registrationFactory = $registrationFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::registrations');
    }

    /**
     * Save registration action
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

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The registration has been saved.'));
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
                    __('Something went wrong while saving the registration.')
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

