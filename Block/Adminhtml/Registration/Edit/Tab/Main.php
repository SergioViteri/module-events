<?php

namespace Zacatrus\Events\Block\Adminhtml\Registration\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Zacatrus\Events\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class Main extends Generic implements TabInterface {
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
     * @var EventCollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param EventCollectionFactory                  $eventCollectionFactory
     * @param CustomerCollectionFactory               $customerCollectionFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        EventCollectionFactory $eventCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm() {
        $model = $this->_coreRegistry->registry('zacatrus_events_registration');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('registration_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Registration Information')]);
        
        if ($model->getId()) {
            $fieldset->addField('registration_id', 'hidden', ['name' => 'registration_id']);
        }
        
        $events = $this->eventCollectionFactory->create();
        $eventOptions = ['' => __('-- Please Select --')];
        foreach ($events as $event) {
            $eventOptions[$event->getId()] = $event->getName();
        }
        
        $fieldset->addField(
            'event_id',
            'select',
            [
                'name' => 'event_id',
                'label' => __('Event'),
                'title' => __('Event'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $eventOptions,
            ]
        );
        
        $customers = $this->customerCollectionFactory->create();
        $customerOptions = ['' => __('-- Please Select --')];
        foreach ($customers as $customer) {
            $customerOptions[$customer->getId()] = $customer->getName() . ' (' . $customer->getEmail() . ')';
        }
        
        $fieldset->addField(
            'customer_id',
            'select',
            [
                'name' => 'customer_id',
                'label' => __('Customer'),
                'title' => __('Customer'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $customerOptions,
            ]
        );
        
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => [
                    'confirmed' => __('Confirmed'),
                    'waitlist' => __('Waitlist'),
                ],
            ]
        );
        
        $fieldset->addField(
            'registration_date',
            'date',
            [
                'name' => 'registration_date',
                'label' => __('Registration Date'),
                'title' => __('Registration Date'),
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'HH:mm:ss',
                'required' => true,
            ]
        );
        
        $form->addValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel() {
        return __('Registration Information');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('Registration Information');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab() {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden() {
        return false;
    }
}

