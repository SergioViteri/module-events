<?php
/**
 * Zacatrus Events Attendance Check Block
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Block\Attendance;

use Zaca\Events\Api\Data\RegistrationInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface;

class Check extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->messageManager = $messageManager;
    }

    /**
     * Get registration
     *
     * @return RegistrationInterface|null
     */
    public function getRegistration()
    {
        return $this->registry->registry('current_registration');
    }

    /**
     * Get meet
     *
     * @return MeetInterface|null
     */
    public function getMeet()
    {
        return $this->registry->registry('current_meet');
    }

    /**
     * Get has location code
     *
     * @return bool
     */
    public function getHasLocationCode()
    {
        return (bool) $this->registry->registry('has_location_code');
    }

    /**
     * Get location ID
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->registry->registry('session_location_id');
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return (string) $this->registry->registry('customer_name');
    }

    /**
     * Get attendance check URL
     *
     * @return string
     */
    public function getAttendanceCheckUrl()
    {
        $registration = $this->getRegistration();
        if (!$registration) {
            return '';
        }
        return $this->getUrl('events/index/attendance', ['registrationId' => $registration->getRegistrationId()]);
    }

    /**
     * Format date and time
     *
     * @param string $date
     * @return string
     */
    public function formatDateTime($date)
    {
        return date('d/m/Y H:i', strtotime($date));
    }

    /**
     * Get messages HTML
     *
     * @return string
     */
    public function getMessagesHtml()
    {
        $messages = $this->messageManager->getMessages(true);
        if ($messages->getCount() > 0) {
            $messagesBlock = $this->getLayout()->getBlock('messages');
            if ($messagesBlock) {
                return $messagesBlock->setMessages($messages)->getGroupedHtml();
            }
        }
        return '';
    }
}
