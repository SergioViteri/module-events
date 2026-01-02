<?php
/**
 * Zacatrus Events Admin Participant Grid
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Participant;

use Psr\Log\LoggerInterface;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Zaca\Events\Model\RegistrationFactory
     */
    protected $_registrationFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Zaca\Events\Model\RegistrationFactory $registrationFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Zaca\Events\Model\RegistrationFactory $registrationFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->_registrationFactory = $registrationFactory;
        $this->moduleManager = $moduleManager;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('participantGrid');
        $this->setDefaultSort('registration_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('participant_filter');
        $this->setFilterVisibility(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->logger->info('[Participant Grid] _prepareCollection() called');
        
        try {
            $collection = $this->_registrationFactory->create()->getCollection();
            $this->logger->info('[Participant Grid] Collection created, initial count: ' . $collection->getSize());
            
            // Join with customer table to get customer name
            $customerTable = $this->resourceConnection->getTableName('customer_entity');
            $collection->getSelect()->joinLeft(
                ['customer' => $customerTable],
                'main_table.customer_id = customer.entity_id',
                [
                    'customer_name' => new \Magento\Framework\DB\Sql\Expression("CONCAT(customer.firstname, ' ', customer.lastname)")
                ]
            );
            
            // Join with meet table to get meet name
            $meetTable = $this->resourceConnection->getTableName('zaca_events_meet');
            $collection->getSelect()->joinLeft(
                ['meet' => $meetTable],
                'main_table.meet_id = meet.meet_id',
                ['meet_name' => 'meet.name']
            );
            
            $this->setCollection($collection);
            
            // Log filter data before parent call
            $filterData = $this->getFilterData();
            if ($filterData) {
                $this->logger->info('[Participant Grid] Filter data before parent::_prepareCollection(): ' . json_encode($filterData->getData()));
            }
            
            $this->logger->info('[Participant Grid] Calling parent::_prepareCollection()');
            parent::_prepareCollection();
            
            $this->logger->info('[Participant Grid] After parent::_prepareCollection(), collection count: ' . $collection->getSize());
            
            // Log the SQL query
            $select = $collection->getSelect();
            if ($select) {
                $this->logger->info('[Participant Grid] SQL Query: ' . $select->__toString());
            }
            
            return $this;
        } catch (\Exception $e) {
            $this->logger->error('[Participant Grid] Error in _prepareCollection(): ' . $e->getMessage());
            $this->logger->error('[Participant Grid] Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'registration_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'registration_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'customer_name',
            [
                'header' => __('Customer Name'),
                'index' => 'customer_name',
                'type' => 'text',
                'filter_condition_callback' => [$this, '_filterCustomerName'],
            ]
        );

        $this->addColumn(
            'meet_name',
            [
                'header' => __('Meet Name'),
                'index' => 'meet_name',
                'type' => 'text',
                'filter_condition_callback' => [$this, '_filterMeetName'],
            ]
        );
/*
        $this->addColumn(
            'meet_id',
            [
                'header' => __('Meet ID'),
                'index' => 'meet_id',
                'type' => 'number',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'customer_id',
            [
                'header' => __('Customer ID'),
                'index' => 'customer_id',
                'type' => 'number',
                'filter' => false,
            ]
        );
*/
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => [
                    '' => __('All'),
                    'confirmed' => __('Confirmed'),
                    'waitlist' => __('Waitlist'),
                ],
            ]
        );

        $this->addColumn(
            'attendance_count',
            [
                'header' => __('Attendance Count'),
                'index' => 'attendance_count',
                'type' => 'number',
                'header_css_class' => 'col-attendance-count',
                'column_css_class' => 'col-attendance-count',
            ]
        );

        $this->addColumn(
            'registration_date',
            [
                'header' => __('Registration Date'),
                'index' => 'registration_date',
                'type' => 'datetime',
            ]
        );
/*
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'index' => 'created_at',
                'type' => 'datetime',
            ]
        );
        */

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
        $this->setMassactionIdField('registration_id');
        $this->getMassactionBlock()->setFormFieldName('registration_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('zaca_events/participant/massDelete'),
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
     * @param \Zaca\Events\Model\Registration $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('zaca_events/*/edit', ['registration_id' => $row->getId()]);
    }

    /**
     * Filter callback for customer name
     *
     * @param \Zaca\Events\Model\ResourceModel\Registration\Collection $collection
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return void
     */
    protected function _filterCustomerName($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->getSelect()->where(
            "CONCAT(customer.firstname, ' ', customer.lastname) LIKE ?",
            '%' . $value . '%'
        );
    }

    /**
     * Filter callback for meet name
     *
     * @param \Zaca\Events\Model\ResourceModel\Registration\Collection $collection
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return void
     */
    protected function _filterMeetName($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->getSelect()->where('meet.name LIKE ?', '%' . $value . '%');
    }
}

