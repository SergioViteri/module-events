<?php
/**
 * Zacatrus Events Admin EventType Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\EventType;

use Magento\Backend\App\Action;
use Zaca\Events\Model\EventTypeFactory;

class Delete extends Action
{
    /**
     * @var EventTypeFactory
     */
    protected $eventTypeFactory;

    /**
     * @param Action\Context $context
     * @param EventTypeFactory $eventTypeFactory
     */
    public function __construct(
        Action\Context $context,
        EventTypeFactory $eventTypeFactory
    ) {
        parent::__construct($context);
        $this->eventTypeFactory = $eventTypeFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::event_types');
    }

    /**
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('event_type_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->eventTypeFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The event type has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find an event type to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

