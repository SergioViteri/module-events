<?php
/**
 * Zacatrus Events EventType Repository
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\EventTypeRepositoryInterface;
use Zaca\Events\Model\ResourceModel\EventType as EventTypeResourceModel;
use Zaca\Events\Model\ResourceModel\EventType\CollectionFactory as EventTypeCollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Psr\Log\LoggerInterface;

class EventTypeRepository implements EventTypeRepositoryInterface
{
    /**
     * @var EventTypeResourceModel
     */
    protected $resource;

    /**
     * @var EventTypeFactory
     */
    protected $eventTypeFactory;

    /**
     * @var EventTypeCollectionFactory
     */
    protected $eventTypeCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param EventTypeResourceModel $resource
     * @param EventTypeFactory $eventTypeFactory
     * @param EventTypeCollectionFactory $eventTypeCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventTypeResourceModel $resource,
        EventTypeFactory $eventTypeFactory,
        EventTypeCollectionFactory $eventTypeCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->eventTypeFactory = $eventTypeFactory;
        $this->eventTypeCollectionFactory = $eventTypeCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(EventType $eventType): EventType
    {
        try {
            $this->resource->save($eventType);
        } catch (\Exception $e) {
            $this->logger->error('Error saving event type: ' . $e->getMessage());
            throw new CouldNotSaveException(__('Could not save the event type: %1', $e->getMessage()));
        }
        return $eventType;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $eventTypeId): EventType
    {
        $eventType = $this->eventTypeFactory->create();
        $this->resource->load($eventType, $eventTypeId);
        if (!$eventType->getId()) {
            throw new NoSuchEntityException(__('Event type with id "%1" does not exist.', $eventTypeId));
        }
        return $eventType;
    }

    /**
     * @inheritdoc
     */
    public function getByCode(string $code): EventType
    {
        $collection = $this->eventTypeCollectionFactory->create();
        $collection->addFieldToFilter('code', $code);
        $eventType = $collection->getFirstItem();
        
        if (!$eventType->getId()) {
            throw new NoSuchEntityException(__('Event type with code "%1" does not exist.', $code));
        }
        
        return $eventType;
    }

    /**
     * @inheritdoc
     */
    public function delete(EventType $eventType): bool
    {
        try {
            $this->resource->delete($eventType);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting event type: ' . $e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete the event type: %1', $e->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $eventTypeId): bool
    {
        return $this->delete($this->getById($eventTypeId));
    }

    /**
     * @inheritdoc
     */
    public function getActiveEventTypes()
    {
        $collection = $this->eventTypeCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('sort_order', 'ASC');
        return $collection;
    }
}

