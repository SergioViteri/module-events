<?php

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TimeSlotSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Zaca\Events\Api\Data\TimeSlotInterface[]
     */
    public function getItems();

    /**
     * @param \Zaca\Events\Api\Data\TimeSlotInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
