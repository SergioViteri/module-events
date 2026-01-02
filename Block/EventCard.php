<?php
/**
 * Zacatrus Events Event Card Block
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block;

use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Api\EventTypeRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EventCard extends Template
{
    /**
     * @var MeetInterface
     */
    protected $event;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var EventTypeRepositoryInterface
     */
    protected $eventTypeRepository;

    /**
     * @param Context $context
     * @param LocationFactory $locationFactory
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param Session $customerSession
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param EventTypeRepositoryInterface $eventTypeRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        LocationFactory $locationFactory,
        RegistrationRepositoryInterface $registrationRepository,
        Session $customerSession,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        EventTypeRepositoryInterface $eventTypeRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->locationFactory = $locationFactory;
        $this->registrationRepository = $registrationRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->eventTypeRepository = $eventTypeRepository;
    }

    /**
     * Set meet
     *
     * @param MeetInterface $event
     * @return $this
     */
    public function setEvent(MeetInterface $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get meet
     *
     * @return MeetInterface|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Get location name
     *
     * @return string
     */
    public function getStoreName()
    {
        if (!$this->event) {
            return '';
        }

        try {
            $location = $this->locationFactory->create()->load($this->event->getLocationId());
            if ($location->getId()) {
                $name = $location->getName();
                return $name;
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format event date
     *
     * @param string $date
     * @return string
     */
    public function formatEventDate($date)
    {
        return date('d/m/Y H:i', strtotime($date));
    }

    /**
     * Get event type label
     *
     * @return string
     */
    public function getEventTypeLabel()
    {
        if (!$this->event) {
            return '';
        }

        $meetTypeCode = $this->event->getMeetType();
        if (empty($meetTypeCode)) {
            return '';
        }

        try {
            $eventType = $this->eventTypeRepository->getByCode($meetTypeCode);
            return __($eventType->getName());
        } catch (\Exception $e) {
            // Fallback to code if event type not found
            return $meetTypeCode;
        }
    }

    /**
     * Get available slots
     *
     * @return int
     */
    public function getAvailableSlots()
    {
        if (!$this->event) {
            return 0;
        }

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
                ->addFilter('status', 'confirmed')
                ->create()
        );

        $confirmed = $collection->getTotalCount();
        return max(0, $this->event->getMaxSlots() - $confirmed);
    }

    /**
     * Check if customer is registered
     *
     * @return bool
     */
    public function isCustomerRegistered()
    {
        if (!$this->event || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customerId = $this->customerSession->getCustomerId();
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        return $collection->getTotalCount() > 0;
    }

    /**
     * Get registration status
     *
     * @return string|null
     */
    public function getRegistrationStatus()
    {
        if (!$this->isCustomerRegistered()) {
            return null;
        }

        $customerId = $this->customerSession->getCustomerId();
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $collection = $this->registrationRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('meet_id', $this->event->getMeetId())
                ->addFilter('customer_id', $customerId)
                ->create()
        );

        if ($collection->getTotalCount() > 0) {
            $items = $collection->getItems();
            return reset($items)->getStatus();
        }

        return null;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get registration URL
     *
     * @param int $meetId
     * @return string
     */
    public function getRegisterUrl($meetId)
    {
        return $this->getUrl('events/index/register', ['_query' => ['meetId' => $meetId]]);
    }

    /**
     * Get unregister URL
     *
     * @param int $meetId
     * @return string
     */
    public function getUnregisterUrl($meetId)
    {
        return $this->getUrl('events/index/unregister', ['_query' => ['meetId' => $meetId]]);
    }
}

