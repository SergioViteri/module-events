<?php

namespace Zaca\Events\Model\Ludoteca;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaca\Events\Api\Data\TableBookingSlotInterface;
use Zaca\Events\Api\TableBookingSlotRepositoryInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot as TableBookingSlotResource;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot\CollectionFactory;

class TableBookingSlotRepository implements TableBookingSlotRepositoryInterface
{
    private TableBookingSlotResource $resource;
    private TableBookingSlotFactory $factory;
    private CollectionFactory $collectionFactory;
    private SearchResultsInterfaceFactory $searchResultsFactory;
    private CollectionProcessorInterface $collectionProcessor;

    public function __construct(
        TableBookingSlotResource $resource,
        TableBookingSlotFactory $factory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
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

    public function getById(int $slotId): TableBookingSlotInterface
    {
        $model = $this->factory->create();
        $this->resource->load($model, $slotId);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Booking slot with id "%1" does not exist.', $slotId));
        }
        return $model;
    }
}
