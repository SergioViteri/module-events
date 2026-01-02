<?php
/**
 * Zacatrus Events Admin Location Edit Tab Main
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Location\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('zaca_events_location');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('location_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Location Information')]);
        
        if ($model->getId()) {
            $fieldset->addField('location_id', 'hidden', ['name' => 'location_id']);
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
        
        $fieldset->addField(
            'code',
            'text',
            [
                'name' => 'code',
                'label' => __('Location Code'),
                'title' => __('Location Code'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'class' => 'validate-alphanum',
                'note' => __('Unique code for attendance validation. Alphanumeric characters only.'),
            ]
        );
        
        $fieldset->addField(
            'address',
            'text',
            [
                'name' => 'address',
                'label' => __('Address'),
                'title' => __('Address'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => __('City'),
                'title' => __('City'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'postal_code',
            'text',
            [
                'name' => 'postal_code',
                'label' => __('Postal Code'),
                'title' => __('Postal Code'),
                'required' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        
        $fieldset->addField(
            'country',
            'text',
            [
                'name' => 'country',
                'label' => __('Country Code'),
                'title' => __('Country Code (2 letters)'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'class' => 'validate-length maximum-length-2',
            ]
        );
        
        $fieldset->addField(
            'latitude',
            'text',
            [
                'name' => 'latitude',
                'label' => __('Latitude'),
                'title' => __('Latitude'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'class' => 'validate-number',
            ]
        );
        
        $fieldset->addField(
            'longitude',
            'text',
            [
                'name' => 'longitude',
                'label' => __('Longitude'),
                'title' => __('Longitude'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'class' => 'validate-number',
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
    public function getTabLabel()
    {
        return __('Location Information');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Location Information');
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

