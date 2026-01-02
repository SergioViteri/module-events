<?php
/**
 * Zacatrus Events Admin EventType Edit Block
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\EventType;

/**
 * Block for edit page
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Init container
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'event_type_id';
        $this->_blockGroup = 'Zaca_Events';
        $this->_controller = 'adminhtml_eventtype';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ],
            ],
            -100
        );
    }

    /**
     * Get edit form container header text
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeaderText()
    {
        $model = $this->_coreRegistry->registry('zaca_events_event_type');
        if ($model && $model->getId()) {
            return __("Edit Event Type '%1'", $this->escapeHtml($model->getName()));
        } else {
            return __('New Event Type');
        }
    }

    /**
     * Retrieve the save and continue edit Url
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('zaca_events/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }
}

