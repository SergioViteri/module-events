<?php
/**
 * Zacatrus Events League Repository
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model;

use Zacatrus\Events\Api\LeagueRepositoryInterface;
use Zacatrus\Events\Api\Data\LeagueInterface;
use Zacatrus\Events\Api\Data\LeagueInterfaceFactory;
use Zacatrus\Events\Model\ResourceModel\League as LeagueResourceModel;
use Zacatrus\Events\Model\ResourceModel\League\CollectionFactory as LeagueCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class LeagueRepository implements LeagueRepositoryInterface
{
    /**
     * @var LeagueResourceModel
     */
    protected $resource;

    /**
     * @var LeagueInterfaceFactory
     */
    protected $leagueFactory;

    /**
     * @var LeagueCollectionFactory
     */
    protected $leagueCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param LeagueResourceModel $resource
     * @param LeagueInterfaceFactory $leagueFactory
     * @param LeagueCollectionFactory $leagueCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        LeagueResourceModel $resource,
        LeagueInterfaceFactory $leagueFactory,
        LeagueCollectionFactory $leagueCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->leagueFactory = $leagueFactory;
        $this->leagueCollectionFactory = $leagueCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(LeagueInterface $league): LeagueInterface
    {
        try {
            $this->resource->save($league);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the league: %1', $exception->getMessage()));
        }
        return $league;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $leagueId): LeagueInterface
    {
        $league = $this->leagueFactory->create();
        $this->resource->load($league, $leagueId);
        if (!$league->getLeagueId()) {
            throw new NoSuchEntityException(__('League with id "%1" does not exist.', $leagueId));
        }
        return $league;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->leagueCollectionFactory->create();
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
    public function delete(LeagueInterface $league): bool
    {
        try {
            $this->resource->delete($league);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the league: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $leagueId): bool
    {
        return $this->delete($this->getById($leagueId));
    }
}

