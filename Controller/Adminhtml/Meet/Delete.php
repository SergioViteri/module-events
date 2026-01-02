<?php
/**
 * Zacatrus Events Admin Meet Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Meet;

use Magento\Backend\App\Action;
use Zaca\Events\Model\MeetFactory;

class Delete extends Action
{
    /**
     * @var MeetFactory
     */
    protected $meetFactory;

    /**
     * @param Action\Context $context
     * @param MeetFactory $meetFactory
     */
    public function __construct(
        Action\Context $context,
        MeetFactory $meetFactory
    ) {
        parent::__construct($context);
        $this->meetFactory = $meetFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::meets');
    }

    /**
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('meet_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->meetFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The meet has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a meet to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

