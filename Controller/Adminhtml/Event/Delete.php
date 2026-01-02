<?php
namespace Zacatrus\Events\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Zacatrus\Events\Model\EventFactory;

class Delete extends Action
{
    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @param Action\Context $context
     * @param EventFactory $eventFactory
     */
    public function __construct(
        Action\Context $context,
        EventFactory $eventFactory
    ) {
        parent::__construct($context);
        $this->eventFactory = $eventFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zacatrus_Events::events_manage');
    }

    /**
     * Delete event action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('event_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->eventFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The event has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['event_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find an event to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}

