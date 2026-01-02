<?php
/**
 * Zacatrus Events Admin Location Grid
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Location;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Zaca\Events\Model\LocationFactory
     */
    protected $_locationFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Zaca\Events\Model\LocationFactory $locationFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Zaca\Events\Model\LocationFactory $locationFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_locationFactory = $locationFactory;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('locationGrid');
        $this->setDefaultSort('location_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('location_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_locationFactory->create()->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'location_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'location_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
            ]
        );

        $this->addColumn(
            'address',
            [
                'header' => __('Address'),
                'index' => 'address',
            ]
        );

        $this->addColumn(
            'city',
            [
                'header' => __('City'),
                'index' => 'city',
            ]
        );

        $this->addColumn(
            'postal_code',
            [
                'header' => __('Postal Code'),
                'index' => 'postal_code',
            ]
        );

        $this->addColumn(
            'country',
            [
                'header' => __('Country'),
                'index' => 'country',
            ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Is Active'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => [0 => __('No'), 1 => __('Yes')],
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'index' => 'created_at',
                'type' => 'datetime',
            ]
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('location_id');
        $this->getMassactionBlock()->setFormFieldName('location_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('zaca_events/location/massDelete'),
                'confirm' => __('Are you sure?'),
            ]
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('zaca_events/*/grid', ['_current' => true]);
    }

    /**
     * @param \Zaca\Events\Model\Location $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/edit', ['location_id' => $row->getId()]);
    }
}

