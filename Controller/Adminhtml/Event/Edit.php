<?php
namespace Zaca\Events\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\EventFactory;

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
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @param Action\Context                 $context
     * @param Registry    $registry
     * @param Session $adminSession
     * @param EventFactory     $eventFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        EventFactory $eventFactory
    ) {        
        $this->_coreRegistry = $registry;
        $this->adminSession = $adminSession;
        $this->eventFactory = $eventFactory;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::events_manage');
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
        $resultPage->setActiveMenu('Zaca_Events::events_manage')
            ->addBreadcrumb(__('Events'), __('Events'))
            ->addBreadcrumb(__('Manage Events'), __('Manage Events'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('event_id');
        $model = $this->eventFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This event no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_event', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Event') : __('New Event'),
            $id ? __('Edit Event') : __('New Event')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Events'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Event')
        );

        return $resultPage;
    }
}

