<?php
namespace Zacatrus\Events\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zacatrus\Events\Model\EventFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param EventFactory $eventFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        EventFactory $eventFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
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
     * Save event action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->eventFactory->create();
            $id = $this->getRequest()->getParam('event_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The event has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['event_id' => $model->getId(), '_current' => true]
                    );
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while saving the event.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['event_id' => $this->getRequest()->getParam('event_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

