<?php
/**
 * Zacatrus Events Admin Location Mass Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Location;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\LocationFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @param Context $context
     * @param LocationFactory $locationFactory
     */
    public function __construct(
        Context $context,
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
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $locationIds = $this->getRequest()->getParam('location_id');
        if (!is_array($locationIds)) {
            $this->messageManager->addError(__('Please select location(s).'));
        } else {
            try {
                $count = 0;
                foreach ($locationIds as $locationId) {
                    $location = $this->locationFactory->create()->load($locationId);
                    if ($location->getId()) {
                        $location->delete();
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

