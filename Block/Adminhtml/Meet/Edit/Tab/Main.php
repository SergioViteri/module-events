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
use Zaca\Events\Api\ThemeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

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
     * @var ThemeRepositoryInterface
     */
    protected $themeRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param LocationCollectionFactory              $locationCollectionFactory
     * @param EventTypeRepositoryInterface           $eventTypeRepository
     * @param ThemeRepositoryInterface               $themeRepository
     * @param SearchCriteriaBuilderFactory           $searchCriteriaBuilderFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        LocationCollectionFactory $locationCollectionFactory,
        EventTypeRepositoryInterface $eventTypeRepository,
        ThemeRepositoryInterface $themeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->themeRepository = $themeRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
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
        
        // Get themes from database
        $themeOptions = ['' => __('-- Please Select --')];
        try {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter('is_active', 1);
            $searchCriteria = $searchCriteriaBuilder->create();
            $themes = $this->themeRepository->getList($searchCriteria);
            foreach ($themes->getItems() as $theme) {
                $themeOptions[$theme->getThemeId()] = $theme->getName();
            }
        } catch (\Exception $e) {
            // If themes don't exist yet, just use empty options
        }
        
        $fieldset->addField(
            'theme_id',
            'select',
            [
                'name' => 'theme_id',
                'label' => __('Theme'),
                'title' => __('Theme'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'options' => $themeOptions,
                'note' => __('Optional. Select a theme for this meet.'),
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
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('End Date'),
                'title' => __('End Date (for recurring events)'),
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'HH:mm:ss',
                'required' => false,
                'note' => __('Optional. Set an end date for recurring events to limit when they stop appearing.'),
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
            'info_url_path',
            'text',
            [
                'name' => 'info_url_path',
                'label' => __('Additional Info Link'),
                'title' => __('Additional Info Link'),
                'disabled' => $isElementDisabled,
                'note' => __('Enter relative path (e.g., \'eventos/torneo-2024\'). The full URL will be: http://zacatrus.es/[path]'),
                'after_element_html' => '<div style="margin-top: 5px;"><strong>http://zacatrus.es/</strong><span id="meet_info_url_path_preview"></span></div><script>
                    require(["jquery"], function($) {
                        var $input = $("#meet_info_url_path");
                        var $preview = $("#meet_info_url_path_preview");
                        function updatePreview() {
                            var value = $input.val();
                            $preview.text(value || "");
                        }
                        $input.on("input change", updatePreview);
                        updatePreview();
                    });
                </script>',
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
        
        $fieldset->addField(
            'reminder_days',
            'text',
            [
                'name' => 'reminder_days',
                'label' => __('Reminder Days'),
                'title' => __('Reminder Days'),
                'disabled' => $isElementDisabled,
                'note' => __('Comma-separated days before event to send reminders (e.g., \'7,3,1\'). Leave empty to disable reminders.'),
                'after_element_html' => '<script>
                    require(["jquery"], function($) {
                        var $input = $("#meet_reminder_days");
                        $input.on("blur", function() {
                            var value = $(this).val().trim();
                            if (value === "") {
                                return; // Empty is allowed
                            }
                            // Remove spaces and validate format
                            var cleaned = value.replace(/\s+/g, "");
                            var parts = cleaned.split(",");
                            var valid = true;
                            for (var i = 0; i < parts.length; i++) {
                                var num = parseInt(parts[i], 10);
                                if (isNaN(num) || num <= 0) {
                                    valid = false;
                                    break;
                                }
                            }
                            if (!valid) {
                                alert("' . __('Please enter only positive integers separated by commas (e.g., 7,3,1)') . '");
                                $(this).focus();
                            } else {
                                // Update with cleaned value (no spaces)
                                $(this).val(cleaned);
                            }
                        });
                    });
                </script>',
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

