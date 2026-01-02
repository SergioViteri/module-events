<?php
/**
 * Zacatrus Events Admin Location Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Location;

use Magento\Backend\App\Action;
use Zaca\Events\Model\LocationFactory;

class Delete extends Action
{
    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @param Action\Context $context
     * @param LocationFactory $locationFactory
     */
    public function __construct(
        Action\Context $context,
        LocationFactory $locationFactory
    ) {
        parent::__construct($context);
        $this->locationFactory = $locationFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::locations');
    }

    /**
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('location_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->locationFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The location has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a location to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

