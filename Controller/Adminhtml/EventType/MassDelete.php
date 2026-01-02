<?php
/**
 * Zacatrus Events Admin EventType Mass Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\EventType;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\EventTypeFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    /**
     * @var EventTypeFactory
     */
    protected $eventTypeFactory;

    /**
     * @param Context $context
     * @param EventTypeFactory $eventTypeFactory
     */
    public function __construct(
        Context $context,
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
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $eventTypeIds = $this->getRequest()->getParam('event_type_id');
        if (!is_array($eventTypeIds)) {
            $this->messageManager->addError(__('Please select event type(s).'));
        } else {
            try {
                $count = 0;
                foreach ($eventTypeIds as $eventTypeId) {
                    $eventType = $this->eventTypeFactory->create()->load($eventTypeId);
                    if ($eventType->getId()) {
                        $eventType->delete();
                        $count++;
                    }
                }
                $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $count));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}

