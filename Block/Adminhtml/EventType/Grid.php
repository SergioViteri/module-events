<?php
/**
 * Zacatrus Events Admin EventType Grid
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\EventType;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Zaca\Events\Model\EventTypeFactory
     */
    protected $_eventTypeFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Zaca\Events\Model\EventTypeFactory $eventTypeFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Zaca\Events\Model\EventTypeFactory $eventTypeFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_eventTypeFactory = $eventTypeFactory;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('eventTypeGrid');
        $this->setDefaultSort('sort_order');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('event_type_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_eventTypeFactory->create()->getCollection();
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
            'event_type_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'event_type_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'code',
            [
                'header' => __('Code'),
                'index' => 'code',
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
            'sort_order',
            [
                'header' => __('Sort Order'),
                'type' => 'number',
                'index' => 'sort_order',
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
        $this->setMassactionIdField('event_type_id');
        $this->getMassactionBlock()->setFormFieldName('event_type_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('zaca_events/eventtype/massDelete'),
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
     * @param \Zaca\Events\Model\EventType $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/edit', ['event_type_id' => $row->getId()]);
    }
}

