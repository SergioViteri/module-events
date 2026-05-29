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
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking as TableBookingResource;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking\Collection;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking\CollectionFactory;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot\Collection as SlotCollection;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBookingSlot\CollectionFactory as SlotCollectionFactory;

class TableBookingRepository implements TableBookingRepositoryInterface
{
    private TableBookingResource $resource;
    private TableBookingFactory $factory;
    private CollectionFactory $collectionFactory;
    private SlotCollectionFactory $slotCollectionFactory;
    private SearchResultsInterfaceFactory $searchResultsFactory;
    private CollectionProcessorInterface $collectionProcessor;
    private LoggerInterface $logger;

    public function __construct(
        TableBookingResource $resource,
        TableBookingFactory $factory,
        CollectionFactory $collectionFactory,
        SlotCollectionFactory $slotCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->slotCollectionFactory = $slotCollectionFactory;
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

    public function save(TableBooking $booking): TableBooking
    {
        try {
            $this->resource->save($booking);
        } catch (\Exception $e) {
            $this->logger->error('[Ludoteca] Error saving booking: ' . $e->getMessage());
            throw new CouldNotSaveException(__('No se pudo guardar la reserva: %1', $e->getMessage()));
        }
        return $booking;
    }

    public function getById(int $id): TableBooking
    {
        $model = $this->factory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Reserva con id "%1" no existe.', $id));
        }
        return $model;
    }

    public function getByUnsubscribeCode(string $code): TableBooking
    {
        $model = $this->factory->create();
        $this->resource->load($model, $code, 'unsubscribe_code');
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Reserva con código "%1" no existe.', $code));
        }
        return $model;
    }

    public function delete(TableBooking $booking): bool
    {
        try {
            $this->resource->delete($booking);
        } catch (\Exception $e) {
            $this->logger->error('[Ludoteca] Error deleting booking: ' . $e->getMessage());
            throw new CouldNotDeleteException(__('No se pudo borrar la reserva: %1', $e->getMessage()));
        }
        return true;
    }

    public function getByCustomer(int $customerId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setOrder('booking_date', 'DESC');
        $collection->getSelect()->order('created_at DESC');
        return $collection;
    }

    public function getSlots(int $bookingId): SlotCollection
    {
        $collection = $this->slotCollectionFactory->create();
        $collection->addFieldToFilter('booking_id', $bookingId);
        $collection->getSelect()->joinLeft(
            ['ts' => $collection->getResource()->getTable('zaca_events_time_slot')],
            'ts.time_slot_id = main_table.time_slot_id',
            ['start_time', 'end_time']
        );
        $collection->getSelect()->order('ts.start_time ASC');
        return $collection;
    }
}
