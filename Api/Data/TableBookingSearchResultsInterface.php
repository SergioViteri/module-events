<?php

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TableBookingSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Zaca\Events\Api\Data\TableBookingInterface[]
     */
    public function getItems();

    /**
     * @param \Zaca\Events\Api\Data\TableBookingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
