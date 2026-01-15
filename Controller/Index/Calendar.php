<?php
/**
 * Zacatrus Events Calendar Controller (iCal Download)
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Helper\Calendar as CalendarHelper;
use Zaca\Events\Model\LocationFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Zaca\Events\Helper\Data as EventsHelper;

class Calendar extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var CalendarHelper
     */
    protected $calendarHelper;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param MeetRepositoryInterface $meetRepository
     * @param CalendarHelper $calendarHelper
     * @param LocationFactory $locationFactory
     * @param RawFactory $resultRawFactory
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        MeetRepositoryInterface $meetRepository,
        CalendarHelper $calendarHelper,
        LocationFactory $locationFactory,
        RawFactory $resultRawFactory,
        EventsHelper $eventsHelper
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->meetRepository = $meetRepository;
        $this->calendarHelper = $calendarHelper;
        $this->locationFactory = $locationFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Execute action
     *
     * @return Raw|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Check if module is enabled
        $isEnabled = $this->scopeConfig->getValue(
            'zaca_events/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        $meetId = (int) $this->getRequest()->getParam('id');
        
        if (!$meetId) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        try {
            $meet = $this->meetRepository->getById($meetId);
            
            if (!$meet->getIsActive()) {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
            }

            // Load location
            $location = null;
            try {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                if (!$location->getId()) {
                    $location = null;
                }
            } catch (\Exception $e) {
                // Location not found, continue without it
                $location = null;
            }

            // Generate iCal content
            $icalContent = $this->calendarHelper->generateIcalContent($meet, $location);

            // Create filename
            $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $meet->getName()) . '.ics';

            // Return iCal file
            $result = $this->resultRawFactory->create();
            $result->setHeader('Content-Type', 'text/calendar; charset=utf-8');
            $result->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $result->setHeader('Content-Length', strlen($icalContent));
            $result->setContents($icalContent);

            return $result;
        } catch (NoSuchEntityException $e) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        } catch (\Exception $e) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }
    }
}

