<?php
namespace Zaca\Events\Controller\Adminhtml\League;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\LeagueFactory;

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
     * @var LeagueFactory
     */
    protected $leagueFactory;

    /**
     * @param Action\Context                 $context
     * @param Registry    $registry
     * @param Session $adminSession
     * @param LeagueFactory     $leagueFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        LeagueFactory $leagueFactory
    ) {        
        $this->_coreRegistry = $registry;
        $this->adminSession = $adminSession;
        $this->leagueFactory = $leagueFactory;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::leagues');
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
        $resultPage->setActiveMenu('Zaca_Events::leagues')
            ->addBreadcrumb(__('Leagues'), __('Leagues'))
            ->addBreadcrumb(__('Manage Leagues'), __('Manage Leagues'));
        return $resultPage;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('league_id');
        $model = $this->leagueFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This league no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('zaca_events_league', $model);

        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit League') : __('New League'),
            $id ? __('Edit League') : __('New League')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Leagues'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New League')
        );

        return $resultPage;
    }
}

