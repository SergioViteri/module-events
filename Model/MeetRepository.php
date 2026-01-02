<?php
/**
 * Zacatrus Events Meet Repository
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Api\Data\MeetInterfaceFactory;
use Zaca\Events\Model\ResourceModel\Meet as MeetResourceModel;
use Zaca\Events\Model\ResourceModel\Meet\CollectionFactory as MeetCollectionFactory;
use Zaca\Events\Model\Meet\RecurrenceGeneratorFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class MeetRepository implements MeetRepositoryInterface
{
    /**
     * @var MeetResourceModel
     */
    protected $resource;

    /**
     * @var MeetInterfaceFactory
     */
    protected $meetFactory;

    /**
     * @var MeetCollectionFactory
     */
    protected $meetCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var RecurrenceGeneratorFactory
     */
    protected $recurrenceGeneratorFactory;

    /**
     * @param MeetResourceModel $resource
     * @param MeetInterfaceFactory $meetFactory
     * @param MeetCollectionFactory $meetCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param RecurrenceGeneratorFactory $recurrenceGeneratorFactory
     */
    public function __construct(
        MeetResourceModel $resource,
        MeetInterfaceFactory $meetFactory,
        MeetCollectionFactory $meetCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        RecurrenceGeneratorFactory $recurrenceGeneratorFactory
    ) {
        $this->resource = $resource;
        $this->meetFactory = $meetFactory;
        $this->meetCollectionFactory = $meetCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->recurrenceGeneratorFactory = $recurrenceGeneratorFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(MeetInterface $meet): MeetInterface
    {
        $isNew = !$meet->getMeetId();
        try {
            $this->resource->save($meet);
            
            // Generate recurring meets if this is a new recurring meet
            if ($isNew && $meet->getRecurrenceType() === MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
                $recurrenceGenerator = $this->recurrenceGeneratorFactory->create();
                $recurrenceGenerator->generateRecurringMeets($meet);
            }
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the meet: %1', $exception->getMessage()));
        }
        return $meet;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $meetId): MeetInterface
    {
        $meet = $this->meetFactory->create();
        $this->resource->load($meet, $meetId);
        if (!$meet->getMeetId()) {
            throw new NoSuchEntityException(__('Meet with id "%1" does not exist.', $meetId));
        }
        return $meet;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->meetCollectionFactory->create();
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
    public function delete(MeetInterface $meet): bool
    {
        try {
            $this->resource->delete($meet);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the meet: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $meetId): bool
    {
        return $this->delete($this->getById($meetId));
    }
}

