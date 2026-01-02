<?php
/**
 * Zacatrus Events Admin Participant Mass Delete Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\RegistrationFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @param Context $context
     * @param RegistrationFactory $registrationFactory
     */
    public function __construct(
        Context $context,
        RegistrationFactory $registrationFactory
    ) {
        parent::__construct($context);
        $this->registrationFactory = $registrationFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::participants');
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $registrationIds = $this->getRequest()->getParam('registration_id');
        if (!is_array($registrationIds)) {
            $this->messageManager->addError(__('Please select participant(s).'));
        } else {
            try {
                $count = 0;
                foreach ($registrationIds as $registrationId) {
                    $registration = $this->registrationFactory->create()->load($registrationId);
                    if ($registration->getId()) {
                        $registration->delete();
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

