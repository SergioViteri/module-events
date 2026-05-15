<?php
/**
 * Zacatrus Events Meet Search Results Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface MeetSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get meets list
     *
     * @return \Zaca\Events\Api\Data\MeetInterface[]
     */
    public function getItems();

    /**
     * Set meets list
     *
     * @param \Zaca\Events\Api\Data\MeetInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
