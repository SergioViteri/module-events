<?php
/**
 * Zacatrus Events Admin Location Save Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Location;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\LocationFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param LocationFactory $locationFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        LocationFactory $locationFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
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
     * Save location action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->locationFactory->create();
            $id = $this->getRequest()->getParam('location_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The location has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['location_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the location.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['location_id' => $this->getRequest()->getParam('location_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

