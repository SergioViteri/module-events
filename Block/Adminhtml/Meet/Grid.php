<?php
/**
 * Zacatrus Events Admin Meet Grid
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Meet;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Zaca\Events\Model\MeetFactory
     */
    protected $_meetFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Zaca\Events\Model\MeetFactory $meetFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Zaca\Events\Model\MeetFactory $meetFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_meetFactory = $meetFactory;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('meetGrid');
        $this->setDefaultSort('meet_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('meet_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_meetFactory->create()->getCollection();
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
            'meet_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'meet_id',
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
            'meet_type',
            [
                'header' => __('Meet Type'),
                'index' => 'meet_type',
            ]
        );

        $this->addColumn(
            'start_date',
            [
                'header' => __('Start Date'),
                'index' => 'start_date',
                'type' => 'datetime',
            ]
        );

        $this->addColumn(
            'max_slots',
            [
                'header' => __('Max Slots'),
                'index' => 'max_slots',
                'type' => 'number',
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
        $this->setMassactionIdField('meet_id');
        $this->getMassactionBlock()->setFormFieldName('meet_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('zaca_events/meet/massDelete'),
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
     * @param \Zaca\Events\Model\Meet $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/edit', ['meet_id' => $row->getId()]);
    }
}

