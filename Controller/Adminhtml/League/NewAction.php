<?php
namespace Zacatrus\Events\Controller\Adminhtml\League;

/**
 * Class NewAction
 */
class NewAction extends \Magento\Backend\App\Action
{
    protected $_resultForwardFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    )
    {
        parent::__construct($context);
        $this->_resultForwardFactory = $resultForwardFactory;
    }
    
    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zacatrus_Events::leagues');
    }
    
    /**
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        return $this->_resultForwardFactory->create()->forward("edit");
    }
}

