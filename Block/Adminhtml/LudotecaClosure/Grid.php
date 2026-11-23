<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaClosure;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelper;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Zaca\Events\Model\ResourceModel\Ludoteca\Closure\CollectionFactory;

class Grid extends Extended
{
    private CollectionFactory $collectionFactory;
    private LocationCollectionFactory $locationCollectionFactory;

    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        CollectionFactory $collectionFactory,
        LocationCollectionFactory $locationCollectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('ludotecaClosureGrid');
        $this->setDefaultSort('start_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('closure_id', [
            'header' => __('ID'),
            'type' => 'number',
            'index' => 'closure_id',
        ]);

        $locations = [];
        foreach ($this->locationCollectionFactory->create() as $loc) {
            $locations[(int) $loc->getId()] = (string) $loc->getName();
        }
        $this->addColumn('location_id', [
            'header' => __('Location'),
            'index' => 'location_id',
            'type' => 'options',
            'options' => $locations,
            'frame_callback' => [$this, 'renderLocation'],
            'filter_condition_callback' => [$this, 'filterLocation'],
        ]);

        $this->addColumn('start_date', [
            'header' => __('Closed From'),
            'index' => 'start_date',
            'type' => 'date',
            'timezone' => false,
        ]);
        $this->addColumn('end_date', [
            'header' => __('Closed Until'),
            'index' => 'end_date',
            'type' => 'date',
            'timezone' => false,
        ]);
        $this->addColumn('reason', [
            'header' => __('Reason'),
            'index' => 'reason',
        ]);
        $this->addColumn('is_active', [
            'header' => __('Active'),
            'index' => 'is_active',
            'type' => 'options',
            'options' => [0 => __('No'), 1 => __('Yes')],
        ]);

        return parent::_prepareColumns();
    }

    public function renderLocation($value, $row, $column, $isExport): string
    {
        return $row->getData('location_id') === null
            ? (string) __('All locations')
            : (string) $value;
    }

    public function filterLocation($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value !== null && $value !== '') {
            $collection->addFieldToFilter('location_id', (int) $value);
        }
        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('closure_id');
        $this->getMassactionBlock()->setFormFieldName('closure_id');
        $this->getMassactionBlock()->addItem('delete', [
            'label' => __('Delete'),
            'url' => $this->getUrl('zaca_events/ludotecaclosure/massDelete'),
            'confirm' => __('Are you sure?'),
        ]);
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('zaca_events/*/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/edit', ['closure_id' => $row->getId()]);
    }
}
