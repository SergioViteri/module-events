<?php
/**
 * Zacatrus Events Store Repository
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model;

use Zacatrus\Events\Api\StoreRepositoryInterface;
use Zacatrus\Events\Api\Data\StoreInterface;
use Zacatrus\Events\Api\Data\StoreInterfaceFactory;
use Zacatrus\Events\Model\ResourceModel\Store as StoreResourceModel;
use Zacatrus\Events\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * @var StoreResourceModel
     */
    protected $resource;

    /**
     * @var StoreInterfaceFactory
     */
    protected $storeFactory;

    /**
     * @var StoreCollectionFactory
     */
    protected $storeCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param StoreResourceModel $resource
     * @param StoreInterfaceFactory $storeFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        StoreResourceModel $resource,
        StoreInterfaceFactory $storeFactory,
        StoreCollectionFactory $storeCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->storeFactory = $storeFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(StoreInterface $store): StoreInterface
    {
        try {
            $this->resource->save($store);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the store: %1', $exception->getMessage()));
        }
        return $store;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $storeId): StoreInterface
    {
        $store = $this->storeFactory->create();
        $this->resource->load($store, $storeId);
        if (!$store->getStoreId()) {
            throw new NoSuchEntityException(__('Store with id "%1" does not exist.', $storeId));
        }
        return $store;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->storeCollectionFactory->create();
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
    public function delete(StoreInterface $store): bool
    {
        try {
            $this->resource->delete($store);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the store: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $storeId): bool
    {
        return $this->delete($this->getById($storeId));
    }
}

