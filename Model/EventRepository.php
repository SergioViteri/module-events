<?php
/**
 * Zacatrus Events Event Repository
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\EventRepositoryInterface;
use Zaca\Events\Api\Data\EventInterface;
use Zaca\Events\Api\Data\EventInterfaceFactory;
use Zaca\Events\Model\ResourceModel\Event as EventResourceModel;
use Zaca\Events\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;
use Zaca\Events\Model\Event\RecurrenceGenerator;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class EventRepository implements EventRepositoryInterface
{
    /**
     * @var EventResourceModel
     */
    protected $resource;

    /**
     * @var EventInterfaceFactory
     */
    protected $eventFactory;

    /**
     * @var EventCollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var RecurrenceGenerator
     */
    protected $recurrenceGenerator;

    /**
     * @param EventResourceModel $resource
     * @param EventInterfaceFactory $eventFactory
     * @param EventCollectionFactory $eventCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param RecurrenceGenerator $recurrenceGenerator
     */
    public function __construct(
        EventResourceModel $resource,
        EventInterfaceFactory $eventFactory,
        EventCollectionFactory $eventCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        RecurrenceGenerator $recurrenceGenerator
    ) {
        $this->resource = $resource;
        $this->eventFactory = $eventFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->recurrenceGenerator = $recurrenceGenerator;
    }

    /**
     * @inheritdoc
     */
    public function save(EventInterface $event): EventInterface
    {
        $isNew = !$event->getEventId();
        try {
            $this->resource->save($event);
            
            // Generate recurring events if this is a new recurring event
            if ($isNew && $event->getRecurrenceType() === EventInterface::RECURRENCE_TYPE_QUINCENAL) {
                $this->recurrenceGenerator->generateRecurringEvents($event);
            }
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the event: %1', $exception->getMessage()));
        }
        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $eventId): EventInterface
    {
        $event = $this->eventFactory->create();
        $this->resource->load($event, $eventId);
        if (!$event->getEventId()) {
            throw new NoSuchEntityException(__('Event with id "%1" does not exist.', $eventId));
        }
        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->eventCollectionFactory->create();
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
    public function delete(EventInterface $event): bool
    {
        try {
            $this->resource->delete($event);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the event: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $eventId): bool
    {
        return $this->delete($this->getById($eventId));
    }
}

