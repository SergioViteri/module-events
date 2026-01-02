<?php
namespace Zaca\Events\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\StoreFactory;

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
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @param Action\Context                 $context
     * @param Registry    $registry
     * @param Session $adminSession
     * @param StoreFactory     $storeFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        StoreFactory $storeFactory
    ) {        
        $this->_coreRegistry = $registry;
        $this->adminSession = $adminSession;
        $this->storeFactory = $storeFactory;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::stores');
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
        $resultPage->setActiveMenu('Zaca_Events::stores')
            ->addBreadcrumb(__('Stores'), __('Stores'))
            ->addBreadcrumb(__('Manage Stores'), __('Manage Stores'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('store_id');
        $model = $this->storeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This store no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_store', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Store') : __('New Store'),
            $id ? __('Edit Store') : __('New Store')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Stores'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Store')
        );

        return $resultPage;
    }
}

