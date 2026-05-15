<?php
/**
 * Zacatrus Events Registration Search Results Interface
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface RegistrationSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get registrations list
     *
     * @return \Zaca\Events\Api\Data\RegistrationInterface[]
     */
    public function getItems();

    /**
     * Set registrations list
     *
     * @param \Zaca\Events\Api\Data\RegistrationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
