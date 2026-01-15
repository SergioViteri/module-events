<?php
/**
 * Zacatrus Events Admin Participant Edit Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

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
        return $this->_authorization->isAllowed('Zaca_Events::participants');
    }

    /**
     * Add participant breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::participants')
            ->addBreadcrumb(__('Participants'), __('Participants'))
            ->addBreadcrumb(__('Manage Participants'), __('Manage Participants'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('registration_id');
        
        // Prevent creating new participants - require an existing ID
        if (!$id) {
            $this->messageManager->addError(__('Cannot create new participants. Please select an existing participant to edit.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        
        $model = $this->registrationFactory->create();
        $model->load($id);
        
        if (!$model->getId()) {
            $this->messageManager->addError(__('This participant no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_participant', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            __('Edit Participant'),
            __('Edit Participant')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Participants'));
        $resultPage->getConfig()->getTitle()->prepend(
            __('Participant #%1', $model->getId())
        );

        return $resultPage;
    }
}

