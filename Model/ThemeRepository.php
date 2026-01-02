<?php
/**
 * Zacatrus Events Theme Repository
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Api\ThemeRepositoryInterface;
use Zaca\Events\Api\Data\ThemeInterface;
use Zaca\Events\Api\Data\ThemeInterfaceFactory;
use Zaca\Events\Model\ResourceModel\Theme as ThemeResourceModel;
use Zaca\Events\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ThemeRepository implements ThemeRepositoryInterface
{
    /**
     * @var ThemeResourceModel
     */
    protected $resource;

    /**
     * @var ThemeInterfaceFactory
     */
    protected $themeFactory;

    /**
     * @var ThemeCollectionFactory
     */
    protected $themeCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param ThemeResourceModel $resource
     * @param ThemeInterfaceFactory $themeFactory
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ThemeResourceModel $resource,
        ThemeInterfaceFactory $themeFactory,
        ThemeCollectionFactory $themeCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->themeFactory = $themeFactory;
        $this->themeCollectionFactory = $themeCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(ThemeInterface $theme): ThemeInterface
    {
        try {
            $this->resource->save($theme);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the theme: %1', $exception->getMessage()));
        }
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $themeId): ThemeInterface
    {
        $theme = $this->themeFactory->create();
        $this->resource->load($theme, $themeId);
        if (!$theme->getThemeId()) {
            throw new NoSuchEntityException(__('Theme with id "%1" does not exist.', $themeId));
        }
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->themeCollectionFactory->create();
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
    public function delete(ThemeInterface $theme): bool
    {
        try {
            $this->resource->delete($theme);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the theme: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $themeId): bool
    {
        return $this->delete($this->getById($themeId));
    }
}

