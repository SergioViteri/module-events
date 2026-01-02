<?php
/**
 * Zacatrus Events Admin EventType Save Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\EventType;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\EventTypeFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var EventTypeFactory
     */
    protected $eventTypeFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param EventTypeFactory $eventTypeFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        EventTypeFactory $eventTypeFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
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
     * Save event type action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->eventTypeFactory->create();
            $id = $this->getRequest()->getParam('event_type_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The event type has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['event_type_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the event type.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['event_type_id' => $this->getRequest()->getParam('event_type_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

