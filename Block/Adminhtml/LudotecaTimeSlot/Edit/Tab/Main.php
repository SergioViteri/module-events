<?php

namespace Zaca\Events\Block\Adminhtml\LudotecaTimeSlot\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot\Edit as EditController;
use Zaca\Events\Model\Config\Source\DayOfWeek;
use Zaca\Events\Model\Config\Source\Location;

class Main extends Generic implements TabInterface
{
    private DayOfWeek $dayOfWeek;
    private Location $locationSource;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        DayOfWeek $dayOfWeek,
        Location $locationSource,
        array $data = []
    ) {
        $this->dayOfWeek = $dayOfWeek;
        $this->locationSource = $locationSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry(EditController::REGISTRY_KEY);

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('time_slot_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Time Slot Information')]);

        if ($model && $model->getId()) {
            $fieldset->addField('time_slot_id', 'hidden', ['name' => 'time_slot_id']);
        }

        $fieldset->addField('location_id', 'select', [
            'name' => 'location_id',
            'label' => __('Location'),
            'title' => __('Location'),
            'required' => true,
            'values' => $this->locationSource->toOptionArray(),
        ]);

        $fieldset->addField('day_of_week', 'select', [
            'name' => 'day_of_week',
            'label' => __('Day of Week'),
            'title' => __('Day of Week'),
            'values' => $this->dayOfWeek->toOptionArray(),
            'note' => __('Choose "All days" to apply this slot to every day of the week.'),
        ]);

        $fieldset->addField('start_time', 'text', [
            'name' => 'start_time',
            'label' => __('Start Time'),
            'title' => __('Start Time'),
            'required' => true,
            'note' => __('Format HH:MM (e.g. 17:00)'),
            'class' => 'validate-no-html-tags',
        ]);

        $fieldset->addField('end_time', 'text', [
            'name' => 'end_time',
            'label' => __('End Time'),
            'title' => __('End Time'),
            'required' => true,
            'note' => __('Format HH:MM (e.g. 19:00)'),
            'class' => 'validate-no-html-tags',
        ]);

        $fieldset->addField('sort_order', 'text', [
            'name' => 'sort_order',
            'label' => __('Sort Order'),
            'class' => 'validate-zero-or-greater-number',
        ]);

        $fieldset->addField('is_active', 'select', [
            'name' => 'is_active',
            'label' => __('Active'),
            'options' => ['1' => __('Yes'), '0' => __('No')],
            'required' => true,
        ]);

        if ($model && !$model->getId()) {
            $model->setData('is_active', 1);
            $model->setData('sort_order', 10);
        }

        if ($model) {
            // Trim seconds for nicer display in the form
            $data = $model->getData();
            foreach (['start_time', 'end_time'] as $f) {
                if (!empty($data[$f]) && is_string($data[$f]) && strlen($data[$f]) >= 5) {
                    $data[$f] = substr($data[$f], 0, 5);
                }
            }
            $form->addValues($data);
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Time Slot Information');
    }

    public function getTabTitle()
    {
        return __('Time Slot Information');
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
