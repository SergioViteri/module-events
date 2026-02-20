<?php
/**
 * Zacatrus Events Admin Participant Edit Tab Main
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Adminhtml\Participant\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Zaca\Events\Model\ResourceModel\Meet\CollectionFactory as MeetCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Zaca\Events\Model\RegistrationFactory;

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
     * @var MeetCollectionFactory
     */
    protected $meetCollectionFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Backend\Model\Auth\Session     $adminSession
     * @param MeetCollectionFactory                  $meetCollectionFactory
     * @param CustomerCollectionFactory               $customerCollectionFactory
     * @param CustomerRepositoryInterface            $customerRepository
     * @param RegistrationFactory                      $registrationFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Model\Auth\Session $adminSession,
        MeetCollectionFactory $meetCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        RegistrationFactory $registrationFactory,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        $this->meetCollectionFactory = $meetCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->registrationFactory = $registrationFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare the form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('zaca_events_participant');
        if (!$model) {
            $model = $this->registrationFactory->create();
        }
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('participant_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Participant Information')]);
        
        if ($model && $model->getId()) {
            $fieldset->addField('registration_id', 'hidden', ['name' => 'registration_id']);
        }
        
        $meets = $this->meetCollectionFactory->create();
        $meetOptions = ['' => __('-- Please Select --')];
        foreach ($meets as $meet) {
            $meetOptions[$meet->getId()] = $meet->getName();
        }
        
        $fieldset->addField(
            'meet_id',
            'select',
            [
                'name' => 'meet_id',
                'label' => __('Meet'),
                'title' => __('Meet'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $meetOptions,
            ]
        );
        
        // Display customer name as read-only (customer cannot be changed)
        $customerName = '';
        if ($model && $model->getId() && $model->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($model->getCustomerId());
                $firstname = $customer->getFirstname() ?: '';
                $lastname = $customer->getLastname() ?: '';
                $name = trim($firstname . ' ' . $lastname);
                if (empty($name)) {
                    $name = $customer->getEmail() ?: __('Customer #%1', $customer->getId());
                }
                $email = $customer->getEmail() ?: '';
                $customerName = $name . ($email ? ' (' . $email . ')' : '');
            } catch (\Exception $e) {
                // If customer cannot be loaded (e.g., was deleted), show fallback
                $customerName = __('Customer #%1 (not found)', $model->getCustomerId());
            }
        }
        
        // Add hidden field to preserve customer_id in form submission
        if ($model && $model->getCustomerId()) {
            $fieldset->addField(
                'customer_id',
                'hidden',
                [
                    'name' => 'customer_id',
                ]
            );
        }
        
        $fieldset->addField(
            'customer_name',
            'note',
            [
                'label' => __('Customer'),
                'title' => __('Customer'),
                'text' => $customerName ?: __('N/A'),
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
            'phone_number',
            'text',
            [
                'name' => 'phone_number',
                'label' => __('Phone Number'),
                'title' => __('Phone Number'),
                'required' => true,
                'note' => __('Enter phone number (9-15 digits). Formatting like +, (, ) is allowed.'),
            ]
        );

        $fieldset->addField(
            'attendee_count',
            'text',
            [
                'name' => 'attendee_count',
                'label' => __('Number of Attendees'),
                'title' => __('Number of Attendees'),
                'required' => true,
                'note' => __('Number of people for this registration (1 to the meet\'s max attendees per subscriber).'),
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
        
        $fieldset->addField(
            'email_reminders_disabled',
            'select',
            [
                'name' => 'email_reminders_disabled',
                'label' => __('Disable Email Reminders'),
                'title' => __('Disable Email Reminders'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'options' => [
                    '0' => __('No'),
                    '1' => __('Yes'),
                ],
                'note' => __('If enabled, this participant will not receive reminder emails for this event.'),
            ]
        );
        
        if ($model) {
            $form->addValues($model->getData());
        }
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
        return __('Participant Information');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Participant Information');
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

