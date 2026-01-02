<?php
/**
 * Zacatrus Events Admin Theme Edit Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\ThemeFactory;

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
     * @var ThemeFactory
     */
    protected $themeFactory;

    /**
     * @param Action\Context                 $context
     * @param Registry    $registry
     * @param Session $adminSession
     * @param ThemeFactory     $themeFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        ThemeFactory $themeFactory
    ) {        
        $this->_coreRegistry = $registry;
        $this->adminSession = $adminSession;
        $this->themeFactory = $themeFactory;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::themes');
    }

    /**
     * Add theme breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::themes')
            ->addBreadcrumb(__('Themes'), __('Themes'))
            ->addBreadcrumb(__('Manage Themes'), __('Manage Themes'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('theme_id');
        $model = $this->themeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This theme no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_theme', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Theme') : __('New Theme'),
            $id ? __('Edit Theme') : __('New Theme')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Themes'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Theme')
        );

        return $resultPage;
    }
}

