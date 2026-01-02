<?php
/**
 * Zacatrus Events Admin Theme Mass Delete Controller
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zaca\Events\Model\ThemeFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    /**
     * @var ThemeFactory
     */
    protected $themeFactory;

    /**
     * @param Context $context
     * @param ThemeFactory $themeFactory
     */
    public function __construct(
        Context $context,
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
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $themeIds = $this->getRequest()->getParam('theme_id');
        if (!is_array($themeIds)) {
            $this->messageManager->addError(__('Please select theme(s).'));
        } else {
            try {
                $count = 0;
                foreach ($themeIds as $themeId) {
                    $theme = $this->themeFactory->create()->load($themeId);
                    if ($theme->getId()) {
                        $theme->delete();
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

