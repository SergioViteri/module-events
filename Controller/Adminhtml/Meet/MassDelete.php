<?php
/**
 * Zacatrus Events Admin Meet Mass Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Meet;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\MeetFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    /**
     * @var MeetFactory
     */
    protected $meetFactory;

    /**
     * @param Context $context
     * @param MeetFactory $meetFactory
     */
    public function __construct(
        Context $context,
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
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $meetIds = $this->getRequest()->getParam('meet_id');
        if (!is_array($meetIds)) {
            $this->messageManager->addError(__('Please select meet(s).'));
        } else {
            try {
                $count = 0;
                foreach ($meetIds as $meetId) {
                    $meet = $this->meetFactory->create()->load($meetId);
                    if ($meet->getId()) {
                        $meet->delete();
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

