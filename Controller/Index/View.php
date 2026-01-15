<?php
/**
 * Zacatrus Events View Controller (Single Event)
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Helper\Data as EventsHelper;

class View extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param MeetRepositoryInterface $meetRepository
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        MeetRepositoryInterface $meetRepository,
        EventsHelper $eventsHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->meetRepository = $meetRepository;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Check if module is enabled
        $isEnabled = $this->scopeConfig->getValue(
            'zaca_events/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            // Module is disabled, redirect to events list
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        $meetId = (int) $this->getRequest()->getParam('id');
        
        if (!$meetId) {
            $this->messageManager->addError(__('Event not found.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        try {
            $meet = $this->meetRepository->getById($meetId);
            
            if (!$meet->getIsActive()) {
                $this->messageManager->addError(__('This event is not available.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
            }

            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set($meet->getName());

            // Disable browser caching for this page
            $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $resultPage->setHeader('Pragma', 'no-cache');
            $resultPage->setHeader('Expires', '0');

            return $resultPage;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__('Event not found.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while loading the event.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }
    }
}

