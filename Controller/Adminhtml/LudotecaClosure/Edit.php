<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaClosure;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Zaca\Events\Model\Ludoteca\ClosureFactory;

class Edit extends Action
{
    public const REGISTRY_KEY = 'zaca_events_ludoteca_closure';

    private Registry $registry;
    private Session $adminSession;
    private ClosureFactory $factory;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        Session $adminSession,
        ClosureFactory $factory
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->adminSession = $adminSession;
        $this->factory = $factory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::ludoteca_closures');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('closure_id');
        $model = $this->factory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Closure not found.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $data = $this->adminSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register(self::REGISTRY_KEY, $model);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Zaca_Events::ludoteca_closures')
            ->addBreadcrumb(__('Ludoteca Closures'), __('Ludoteca Closures'));
        $resultPage->getConfig()->getTitle()->prepend(__('Ludoteca Closures'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Closure') : __('New Closure')
        );
        return $resultPage;
    }
}
