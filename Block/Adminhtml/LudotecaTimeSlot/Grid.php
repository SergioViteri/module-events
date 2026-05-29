<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaTimeSlot;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelper;
use Zaca\Events\Model\Config\Source\DayOfWeek;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Zaca\Events\Model\ResourceModel\Ludoteca\TimeSlot\CollectionFactory;

class Grid extends Extended
{
    private CollectionFactory $collectionFactory;
    private LocationCollectionFactory $locationCollectionFactory;
    private DayOfWeek $dayOfWeekSource;

    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        CollectionFactory $collectionFactory,
        LocationCollectionFactory $locationCollectionFactory,
        DayOfWeek $dayOfWeekSource,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->dayOfWeekSource = $dayOfWeekSource;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('ludotecaTimeSlotGrid');
        $this->setDefaultSort('location_id');
        $this->setDefaultDir('ASC');
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
        $this->addColumn('time_slot_id', [
            'header' => __('ID'),
            'type' => 'number',
            'index' => 'time_slot_id',
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
        ]);

        $dowMap = $this->dayOfWeekSource->toMap();
        unset($dowMap['']);
        $this->addColumn('day_of_week', [
            'header' => __('Day'),
            'index' => 'day_of_week',
            'type' => 'options',
            'options' => $dowMap,
            'frame_callback' => [$this, 'renderDayOfWeek'],
        ]);

        $this->addColumn('start_time', [
            'header' => __('Start'),
            'index' => 'start_time',
        ]);
        $this->addColumn('end_time', [
            'header' => __('End'),
            'index' => 'end_time',
        ]);
        $this->addColumn('sort_order', [
            'header' => __('Sort'),
            'type' => 'number',
            'index' => 'sort_order',
        ]);
        $this->addColumn('is_active', [
            'header' => __('Active'),
            'index' => 'is_active',
            'type' => 'options',
            'options' => [0 => __('No'), 1 => __('Yes')],
        ]);

        return parent::_prepareColumns();
    }

    public function renderDayOfWeek($value, $row, $column, $isExport): string
    {
        if ($value === null || $value === '') {
            return (string) __('All days');
        }
        $map = $this->dayOfWeekSource->toMap();
        return $map[(string) $value] ?? (string) $value;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('time_slot_id');
        $this->getMassactionBlock()->setFormFieldName('time_slot_id');
        $this->getMassactionBlock()->addItem('delete', [
            'label' => __('Delete'),
            'url' => $this->getUrl('zaca_events/ludotecatimeslot/massDelete'),
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
        return $this->getUrl('zaca_events/*/edit', ['time_slot_id' => $row->getId()]);
    }
}
