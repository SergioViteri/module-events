<?php
namespace Zaca\Events\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\RegistrationFactory;

/**
 * Edit form controller
 */
class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @param Action\Context                 $context
     * @param Registry    $registry
     * @param Session $adminSession
     * @param RegistrationFactory     $registrationFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        RegistrationFactory $registrationFactory
    ) {        
        $this->_coreRegistry = $registry;
        $this->adminSession = $adminSession;
        $this->registrationFactory = $registrationFactory;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::registrations');
    }

    /**
     * Add blog breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::registrations')
            ->addBreadcrumb(__('Registrations'), __('Registrations'))
            ->addBreadcrumb(__('Manage Registrations'), __('Manage Registrations'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('registration_id');
        $model = $this->registrationFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This registration no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_registration', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Registration') : __('New Registration'),
            $id ? __('Edit Registration') : __('New Registration')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Registrations'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Registration #%1', $model->getId()) : __('New Registration')
        );

        return $resultPage;
    }
}

