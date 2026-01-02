<?php

namespace Zaca\Events\Block\Adminhtml\League\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Zaca\Events\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

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
     * @var StoreCollectionFactory
     */
    protected $storeCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param StoreCollectionFactory                  $storeCollectionFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        StoreCollectionFactory $storeCollectionFactory,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm() {
        $model = $this->_coreRegistry->registry('zaca_events_league');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('league_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('League Information')]);
        
        if ($model->getId()) {
            $fieldset->addField('league_id', 'hidden', ['name' => 'league_id']);
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
        
        $stores = $this->storeCollectionFactory->create();
        $storeOptions = ['' => __('-- Please Select --')];
        foreach ($stores as $store) {
            $storeOptions[$store->getId()] = $store->getName();
        }
        
        $fieldset->addField(
            'store_id',
            'select',
            [
                'name' => 'store_id',
                'label' => __('Store'),
                'title' => __('Store'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $storeOptions,
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
                'required' => true,
            ]
        );
        
        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('End Date'),
                'title' => __('End Date'),
                'date_format' => 'yyyy-MM-dd',
                'required' => true,
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
    public function getTabLabel() {
        return __('League Information');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('League Information');
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

