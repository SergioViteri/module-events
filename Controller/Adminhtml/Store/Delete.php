<?php
/**
 * Zacatrus Events Admin Store Delete Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Zaca\Events\Model\StoreFactory;

class Delete extends Action
{
    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @param Action\Context $context
     * @param StoreFactory $storeFactory
     */
    public function __construct(
        Action\Context $context,
        StoreFactory $storeFactory
    ) {
        parent::__construct($context);
        $this->storeFactory = $storeFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::stores');
    }

    /**
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('store_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->storeFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The store has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a store to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

