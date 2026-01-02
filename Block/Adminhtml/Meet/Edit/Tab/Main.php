<?php
/**
 * Zacatrus Events Admin Meet Edit Tab Main
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Meet\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Zaca\Events\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Zaca\Events\Api\EventTypeRepositoryInterface;

class Main extends Generic implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollectionFactory;

    /**
     * @var EventTypeRepositoryInterface
     */
    protected $eventTypeRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param LocationCollectionFactory              $locationCollectionFactory
     * @param EventTypeRepositoryInterface           $eventTypeRepository
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        LocationCollectionFactory $locationCollectionFactory,
        EventTypeRepositoryInterface $eventTypeRepository,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->eventTypeRepository = $eventTypeRepository;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('zaca_events_meet');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('meet_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Meet Information')]);
        
        if ($model->getId()) {
            $fieldset->addField('meet_id', 'hidden', ['name' => 'meet_id']);
        }
        
        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $locations = $this->locationCollectionFactory->create();
        $locationOptions = ['' => __('-- Please Select --')];
        foreach ($locations as $location) {
            $locationOptions[$location->getId()] = $location->getName();
        }
        
        $fieldset->addField(
            'location_id',
            'select',
            [
                'name' => 'location_id',
                'label' => __('Location'),
                'title' => __('Location'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $locationOptions,
            ]
        );
        
        // Get event types from database
        $eventTypeOptions = ['' => __('-- Please Select --')];
        $eventTypes = $this->eventTypeRepository->getActiveEventTypes();
        foreach ($eventTypes as $eventType) {
            $eventTypeOptions[$eventType->getCode()] = __($eventType->getName());
        }
        
        $fieldset->addField(
            'meet_type',
            'select',
            [
                'name' => 'meet_type',
                'label' => __('Meet Type'),
                'title' => __('Meet Type'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $eventTypeOptions,
            ]
        );
        
        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Start Date'),
                'title' => __('Start Date'),
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'HH:mm:ss',
                'required' => true,
            ]
        );
        
        $fieldset->addField(
            'duration_minutes',
            'text',
            [
                'name' => 'duration_minutes',
                'label' => __('Duration (Minutes)'),
                'title' => __('Duration (Minutes)'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'max_slots',
            'text',
            [
                'name' => 'max_slots',
                'label' => __('Max Slots'),
                'title' => __('Max Slots'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'description',
            'textarea',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'recurrence_type',
            'select',
            [
                'name' => 'recurrence_type',
                'label' => __('Recurrence Type'),
                'title' => __('Recurrence Type'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => [
                    'none' => __('None'),
                    'quincenal' => __('Biweekly'),
                    'semanal' => __('Weekly'),
                ],
            ]
        );
        
        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Is Active'),
                'title' => __('Is Active'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Yes'), '0' => __('No')],
            ]
        );

        if (!$model->getId()) {
            $model->setData('is_active', '1');
            $model->setData('recurrence_type', 'none');
        }
        
        $form->addValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Meet Information');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Meet Information');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}

