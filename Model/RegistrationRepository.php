<?php
/**
 * Zacatrus Events Registration Repository
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model;

use Zacatrus\Events\Api\RegistrationRepositoryInterface;
use Zacatrus\Events\Api\Data\RegistrationInterface;
use Zacatrus\Events\Api\Data\RegistrationInterfaceFactory;
use Zacatrus\Events\Model\ResourceModel\Registration as RegistrationResourceModel;
use Zacatrus\Events\Model\ResourceModel\Registration\CollectionFactory as RegistrationCollectionFactory;
use Zacatrus\Events\Model\EventRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DB\TransactionFactory;

class RegistrationRepository implements RegistrationRepositoryInterface
{
    /**
     * @var RegistrationResourceModel
     */
    protected $resource;

    /**
     * @var RegistrationInterfaceFactory
     */
    protected $registrationFactory;

    /**
     * @var RegistrationCollectionFactory
     */
    protected $registrationCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @param RegistrationResourceModel $resource
     * @param RegistrationInterfaceFactory $registrationFactory
     * @param RegistrationCollectionFactory $registrationCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param EventRepository $eventRepository
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        RegistrationResourceModel $resource,
        RegistrationInterfaceFactory $registrationFactory,
        RegistrationCollectionFactory $registrationCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        EventRepository $eventRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->resource = $resource;
        $this->registrationFactory = $registrationFactory;
        $this->registrationCollectionFactory = $registrationCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->eventRepository = $eventRepository;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(RegistrationInterface $registration): RegistrationInterface
    {
        try {
            $this->resource->save($registration);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the registration: %1', $exception->getMessage()));
        }
        return $registration;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $registrationId): RegistrationInterface
    {
        $registration = $this->registrationFactory->create();
        $this->resource->load($registration, $registrationId);
        if (!$registration->getRegistrationId()) {
            throw new NoSuchEntityException(__('Registration with id "%1" does not exist.', $registrationId));
        }
        return $registration;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->registrationCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(RegistrationInterface $registration): bool
    {
        try {
            $eventId = $registration->getEventId();
            $this->resource->delete($registration);
            
            // Check if we need to promote someone from waitlist
            $this->promoteFromWaitlist($eventId);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the registration: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $registrationId): bool
    {
        return $this->delete($this->getById($registrationId));
    }

    /**
     * @inheritdoc
     */
    public function registerCustomer(int $customerId, int $eventId): RegistrationInterface
    {
        // Check if already registered
        $collection = $this->registrationCollectionFactory->create();
        $collection->addFieldToFilter('event_id', $eventId)
            ->addFieldToFilter('customer_id', $customerId);
        
        if ($collection->getSize() > 0) {
            throw new CouldNotSaveException(__('You are already registered for this event.'));
        }

        // Get event
        $event = $this->eventRepository->getById($eventId);
        
        // Validate event is active and in the future
        if (!$event->getIsActive()) {
            throw new LocalizedException(__('This event is not active.'));
        }
        
        $now = new \DateTime();
        $startDate = new \DateTime($event->getStartDate());
        if ($startDate <= $now) {
            throw new LocalizedException(__('This event has already started or finished.'));
        }

        // Check available slots
        $confirmedRegistrations = $this->registrationCollectionFactory->create();
        $confirmedRegistrations->addFieldToFilter('event_id', $eventId)
            ->addFieldToFilter('status', RegistrationInterface::STATUS_CONFIRMED);
        
        $status = RegistrationInterface::STATUS_CONFIRMED;
        if ($confirmedRegistrations->getSize() >= $event->getMaxSlots()) {
            $status = RegistrationInterface::STATUS_WAITLIST;
        }

        // Create registration
        $registration = $this->registrationFactory->create();
        $registration->setEventId($eventId)
            ->setCustomerId($customerId)
            ->setStatus($status)
            ->setRegistrationDate($now->format('Y-m-d H:i:s'));

        return $this->save($registration);
    }

    /**
     * @inheritdoc
     */
    public function unregisterCustomer(int $customerId, int $eventId): bool
    {
        $collection = $this->registrationCollectionFactory->create();
        $collection->addFieldToFilter('event_id', $eventId)
            ->addFieldToFilter('customer_id', $customerId);
        
        if ($collection->getSize() === 0) {
            throw new NoSuchEntityException(__('You are not registered for this event.'));
        }

        $registration = $collection->getFirstItem();
        return $this->delete($registration);
    }

    /**
     * Promote first waitlist registration to confirmed when a slot becomes available
     *
     * @param int $eventId
     * @return void
     */
    protected function promoteFromWaitlist(int $eventId): void
    {
        $waitlistCollection = $this->registrationCollectionFactory->create();
        $waitlistCollection->addFieldToFilter('event_id', $eventId)
            ->addFieldToFilter('status', RegistrationInterface::STATUS_WAITLIST)
            ->setOrder('created_at', 'ASC')
            ->setPageSize(1);

        if ($waitlistCollection->getSize() > 0) {
            $waitlistRegistration = $waitlistCollection->getFirstItem();
            $waitlistRegistration->setStatus(RegistrationInterface::STATUS_CONFIRMED);
            $this->save($waitlistRegistration);
        }
    }
}

