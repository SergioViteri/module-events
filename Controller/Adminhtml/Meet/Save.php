<?php
/**
 * Zacatrus Events Admin Meet Save Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Meet;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\MeetFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var MeetFactory
     */
    protected $meetFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param MeetFactory $meetFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        MeetFactory $meetFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
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
     * Save meet action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->meetFactory->create();
            $id = $this->getRequest()->getParam('meet_id');

            if ($id) {
                $model->load($id);
            }

            // Convert empty theme_id to NULL for optional foreign key
            if (isset($data['theme_id']) && $data['theme_id'] === '') {
                $data['theme_id'] = null;
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The meet has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['meet_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the meet.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['meet_id' => $this->getRequest()->getParam('meet_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

