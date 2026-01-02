<?php
/**
 * Zacatrus Events Admin Store Save Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zacatrus\Events\Model\StoreFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param StoreFactory $storeFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        StoreFactory $storeFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->storeFactory = $storeFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zacatrus_Events::stores');
    }

    /**
     * Save store action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->storeFactory->create();
            $id = $this->getRequest()->getParam('store_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The store has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['store_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the store.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['store_id' => $this->getRequest()->getParam('store_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

