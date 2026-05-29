<?php

namespace Zaca\Events\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;

class Location implements OptionSourceInterface
{
    private LocationCollectionFactory $collectionFactory;

    public function __construct(LocationCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Select a location --')]];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('name', 'ASC');
        foreach ($collection as $location) {
            $options[] = [
                'value' => (int) $location->getId(),
                'label' => (string) $location->getName(),
            ];
        }
        return $options;
    }
}
