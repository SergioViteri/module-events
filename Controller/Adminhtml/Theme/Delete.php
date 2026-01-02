<?php
/**
 * Zacatrus Events Admin Theme Delete Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action;
use Zaca\Events\Model\ThemeFactory;

class Delete extends Action
{
    /**
     * @var ThemeFactory
     */
    protected $themeFactory;

    /**
     * @param Action\Context $context
     * @param ThemeFactory $themeFactory
     */
    public function __construct(
        Action\Context $context,
        ThemeFactory $themeFactory
    ) {
        parent::__construct($context);
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
     * Execute delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('theme_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->themeFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The theme has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a theme to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}

