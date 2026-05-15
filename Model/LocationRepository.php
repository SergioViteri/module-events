<?php
/**
 * Zacatrus Events Location Repository
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\LocationRepositoryInterface;
use Zaca\Events\Api\Data\LocationInterface;
use Zaca\Events\Api\Data\LocationInterfaceFactory;
use Zaca\Events\Model\ResourceModel\Location as LocationResourceModel;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class LocationRepository implements LocationRepositoryInterface
{
    /**
     * @var LocationResourceModel
     */
    protected $resource;

    /**
     * @var LocationInterfaceFactory
     */
    protected $locationFactory;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param LocationResourceModel $resource
     * @param LocationInterfaceFactory $locationFactory
     * @param LocationCollectionFactory $locationCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        LocationResourceModel $resource,
        LocationInterfaceFactory $locationFactory,
        LocationCollectionFactory $locationCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->locationFactory = $locationFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(LocationInterface $location): LocationInterface
    {
        try {
            $this->resource->save($location);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the location: %1', $exception->getMessage()));
        }
        return $location;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $locationId): LocationInterface
    {
        $location = $this->locationFactory->create();
        $this->resource->load($location, $locationId);
        if (!$location->getLocationId()) {
            throw new NoSuchEntityException(__('Location with id "%1" does not exist.', $locationId));
        }
        return $location;
    }

    /**
     * @inheritdoc
     */
    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Zaca\Events\Api\Data\LocationSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->locationCollectionFactory->create();
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
    public function delete(LocationInterface $location): bool
    {
        try {
            $this->resource->delete($location);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the location: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $locationId): bool
    {
        return $this->delete($this->getById($locationId));
    }
}
