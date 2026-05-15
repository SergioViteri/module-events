<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\TimeSlotRepositoryInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot as TimeSlotResource;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot\Collection;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot\CollectionFactory;

class TimeSlotRepository implements TimeSlotRepositoryInterface
{
    private TimeSlotResource $resource;
    private TimeSlotFactory $factory;
    private CollectionFactory $collectionFactory;
    private SearchResultsInterfaceFactory $searchResultsFactory;
    private CollectionProcessorInterface $collectionProcessor;
    private LoggerInterface $logger;

    public function __construct(
        TimeSlotResource $resource,
        TimeSlotFactory $factory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());
        return $results;
    }

    public function save(TimeSlot $timeSlot): TimeSlot
    {
        try {
            $this->resource->save($timeSlot);
        } catch (\Exception $e) {
            $this->logger->error('[Ludoteca] Error saving time slot: ' . $e->getMessage());
            throw new CouldNotSaveException(__('No se pudo guardar el turno: %1', $e->getMessage()));
        }
        return $timeSlot;
    }

    public function getById(int $id): TimeSlot
    {
        $model = $this->factory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Turno con id "%1" no existe.', $id));
        }
        return $model;
    }

    public function delete(TimeSlot $timeSlot): bool
    {
        try {
            $this->resource->delete($timeSlot);
        } catch (\Exception $e) {
            $this->logger->error('[Ludoteca] Error deleting time slot: ' . $e->getMessage());
            throw new CouldNotDeleteException(__('No se pudo borrar el turno: %1', $e->getMessage()));
        }
        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    public function getActiveByLocation(int $locationId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('location_id', $locationId);
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('sort_order', 'ASC');
        $collection->getSelect()->order('start_time ASC');
        return $collection;
    }
}
