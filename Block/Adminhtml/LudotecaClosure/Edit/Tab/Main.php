<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaClosure\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Zaca\Events\Controller\Adminhtml\LudotecaClosure\Edit as EditController;
use Zaca\Events\Model\Config\Source\Location;

class Main extends Generic implements TabInterface
{
    private Location $locationSource;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Location $locationSource,
        array $data = []
    ) {
        $this->locationSource = $locationSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry(EditController::REGISTRY_KEY);

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('closure_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Closure Information')]);

        if ($model && $model->getId()) {
            $fieldset->addField('closure_id', 'hidden', ['name' => 'closure_id']);
        }

        // Replace the "-- Select a location --" placeholder with the "every ludoteca" choice.
        $locations = $this->locationSource->toOptionArray();
        $locations[0] = ['value' => '', 'label' => __('All locations')];
        $fieldset->addField('location_id', 'select', [
            'name' => 'location_id',
            'label' => __('Location'),
            'title' => __('Location'),
            'values' => $locations,
            'note' => __('Choose "All locations" to close every ludoteca at once.'),
        ]);

        $fieldset->addField('start_date', 'date', [
            'name' => 'start_date',
            'label' => __('Closed From'),
            'title' => __('Closed From'),
            'required' => true,
            'date_format' => 'yyyy-MM-dd',
            'note' => __('First closed day, included (e.g. 2026-12-05).'),
        ]);

        $fieldset->addField('end_date', 'date', [
            'name' => 'end_date',
            'label' => __('Closed Until'),
            'title' => __('Closed Until'),
            'required' => true,
            'date_format' => 'yyyy-MM-dd',
            'note' => __('Last closed day, included (e.g. 2027-01-10). Bookings open again the day after.'),
        ]);

        $fieldset->addField('reason', 'text', [
            'name' => 'reason',
            'label' => __('Reason'),
            'title' => __('Reason'),
            'maxlength' => 255,
            'note' => __('Internal note, customers do not see it.'),
            'class' => 'validate-no-html-tags',
        ]);

        $fieldset->addField('is_active', 'select', [
            'name' => 'is_active',
            'label' => __('Active'),
            'options' => ['1' => __('Yes'), '0' => __('No')],
            'required' => true,
        ]);

        if ($model && !$model->getId()) {
            $model->setData('is_active', 1);
        }
        if ($model) {
            $form->addValues($model->getData());
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Closure Information');
    }

    public function getTabTitle()
    {
        return __('Closure Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
