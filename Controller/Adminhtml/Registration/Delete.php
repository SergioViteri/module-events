<?php
namespace Zacatrus\Events\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action;
use Zacatrus\Events\Model\RegistrationFactory;

class Delete extends Action
{
    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @param Action\Context $context
     * @param RegistrationFactory $registrationFactory
     */
    public function __construct(
        Action\Context $context,
        RegistrationFactory $registrationFactory
    ) {
        parent::__construct($context);
        $this->registrationFactory = $registrationFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zacatrus_Events::registrations');
    }

    /**
     * Delete registration action
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
                $model->delete();
                $this->messageManager->addSuccess(__('The registration has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['registration_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a registration to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}

