<?php
/**
 * Zacatrus Events Admin Theme Save Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\ThemeFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var ThemeFactory
     */
    protected $themeFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param ThemeFactory $themeFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        ThemeFactory $themeFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->themeFactory = $themeFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::themes');
    }

    /**
     * Save theme action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->themeFactory->create();
            $id = $this->getRequest()->getParam('theme_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The theme has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['theme_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the theme.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['theme_id' => $this->getRequest()->getParam('theme_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

