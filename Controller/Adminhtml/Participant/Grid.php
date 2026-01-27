<?php
/**
 * Zacatrus Events Admin Participant Grid Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Grid extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
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
        try {
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            return $resultLayout;
        } catch (\Exception $e) {
            $this->logger->error('[Participant Grid] Error in execute(): ' . $e->getMessage());
            throw $e;
        }
    }
}

