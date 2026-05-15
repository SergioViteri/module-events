<?php
/**
 * Zacatrus Events Location Search Results Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface LocationSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get locations list
     *
     * @return \Zaca\Events\Api\Data\LocationInterface[]
     */
    public function getItems();

    /**
     * Set locations list
     *
     * @param \Zaca\Events\Api\Data\LocationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
