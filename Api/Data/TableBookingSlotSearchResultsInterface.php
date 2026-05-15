<?php

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TableBookingSlotSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Zaca\Events\Api\Data\TableBookingSlotInterface[]
     */
    public function getItems();

    /**
     * @param \Zaca\Events\Api\Data\TableBookingSlotInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
