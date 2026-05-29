<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaBooking;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelper;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Zaca\Events\Model\ResourceModel\Ludoteca\TableBooking\CollectionFactory;

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
        $this->setId('ludotecaBookingGrid');
        $this->setDefaultSort('booking_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['c' => $collection->getResource()->getTable('customer_entity')],
            'c.entity_id = main_table.customer_id',
            ['customer_email' => 'c.email', 'customer_firstname' => 'c.firstname', 'customer_lastname' => 'c.lastname']
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('booking_id', [
            'header' => __('ID'),
            'type' => 'number',
            'index' => 'booking_id',
        ]);

        $locations = [];
        foreach ($this->locationCollectionFactory->create() as $loc) {
            $locations[(int) $loc->getId()] = (string) $loc->getName();
        }
        $this->addColumn('location_id', [
            'header' => __('Store'),
            'index' => 'location_id',
            'type' => 'options',
            'options' => $locations,
        ]);

        $this->addColumn('booking_date', [
            'header' => __('Date'),
            'index' => 'booking_date',
            'type' => 'date',
        ]);

        $this->addColumn('customer_email', [
            'header' => __('Customer'),
            'index' => 'customer_email',
            'filter_index' => 'c.email',
        ]);

        $this->addColumn('phone_number', [
            'header' => __('Phone'),
            'index' => 'phone_number',
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => [
                'confirmed' => __('Confirmed'),
                'cancelled' => __('Cancelled'),
            ],
        ]);

        $this->addColumn('created_at', [
            'header' => __('Created'),
            'index' => 'created_at',
            'type' => 'datetime',
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'type' => 'action',
            'getter' => 'getBookingId',
            'filter' => false,
            'sortable' => false,
            'actions' => [
                [
                    'caption' => __('View'),
                    'url' => ['base' => 'zaca_events/ludotecabooking/view'],
                    'field' => 'booking_id',
                ],
                [
                    'caption' => __('Cancel'),
                    'url' => ['base' => 'zaca_events/ludotecabooking/cancel'],
                    'field' => 'booking_id',
                    'confirm' => __('Cancel this booking?'),
                ],
            ],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('booking_id');
        $this->getMassactionBlock()->setFormFieldName('booking_id');
        $this->getMassactionBlock()->addItem('cancel', [
            'label' => __('Cancel'),
            'url' => $this->getUrl('zaca_events/ludotecabooking/massCancel'),
            'confirm' => __('Cancel selected bookings?'),
        ]);
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('zaca_events/*/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/view', ['booking_id' => $row->getId()]);
    }
}
