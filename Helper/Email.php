<?php
/**
 * Zacatrus Events Email Helper
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Helper;

use Zaca\Events\Api\Data\RegistrationInterface;
use Zaca\Events\Api\Data\MeetInterface;
use Zaca\Events\Api\MeetRepositoryInterface;
use Zaca\Events\Service\QrCodeGenerator;
use Zaca\Events\Model\LocationFactory;
use Zaca\Events\Helper\Calendar;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class Email extends AbstractHelper
{
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MeetRepositoryInterface
     */
    protected $meetRepository;

    /**
     * @var QrCodeGenerator
     */
    protected $qrCodeGenerator;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Calendar
     */
    protected $calendarHelper;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param StateInterface $inlineTranslation
     * @param Escaper $escaper
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param MeetRepositoryInterface $meetRepository
     * @param QrCodeGenerator $qrCodeGenerator
     * @param LocationFactory $locationFactory
     * @param UrlInterface $urlBuilder
     * @param Calendar $calendarHelper
     * @param State $appState
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        MeetRepositoryInterface $meetRepository,
        QrCodeGenerator $qrCodeGenerator,
        LocationFactory $locationFactory,
        UrlInterface $urlBuilder,
        Calendar $calendarHelper,
        State $appState,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->meetRepository = $meetRepository;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->locationFactory = $locationFactory;
        $this->logger = $context->getLogger();
        $this->urlBuilder = $urlBuilder;
        $this->calendarHelper = $calendarHelper;
        $this->appState = $appState;
        $this->timezone = $timezone;
    }

    /**
     * Send registration email
     *
     * @param RegistrationInterface $registration
     * @param bool $isAdminInitiated
     * @return bool
     */
    public function sendRegistrationEmail(RegistrationInterface $registration, bool $isAdminInitiated = false): bool
    {
        try {
            $this->inlineTranslation->suspend();

            // Get customer data
            $customer = $this->customerRepository->getById($registration->getCustomerId());
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();

            // Get meet data
            $meet = $this->meetRepository->getById($registration->getMeetId());

            // Format date and time - convert from UTC to store timezone
            $store = $this->storeManager->getStore();
            
            // Set store context (required for translations in cron jobs)
            $this->storeManager->setCurrentStore($store->getId());
            
            $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
            
            // Resume translation for recurring event date formatting (needed for translations)
            $this->inlineTranslation->resume();
            
            // For recurring events, use formatted date string (day of week - next occurrence (periodicity))
            $formattedRecurringDate = $this->getFormattedRecurringEventDate($meet, $store->getId());
            if ($formattedRecurringDate) {
                $meetDate = $formattedRecurringDate;
                $meetTime = ''; // Empty for recurring events since date includes time
            } else {
                // For non-recurring events, use separate date and time
                $startDate = $this->timezone->date($meet->getStartDate(), null, false);
                $meetDate = $startDate->format('d/m/Y');
                $meetTime = $startDate->format('H:i');
            }
            
            $this->inlineTranslation->suspend();

            // Format end date if recurring event has end_date (date only, no time)
            $meetEndDate = null;
            if ($isRecurring && $meet->getEndDate()) {
                $endDate = $this->timezone->date($meet->getEndDate(), null, false);
                $meetEndDate = $endDate->format('d/m/Y');
            }

            // Get location data
            $locationName = '';
            $locationAddress = '';
            $location = null;
            try {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                if ($location && $location->getId()) {
                    $locationName = $location->getName();
                    $addressParts = [];
                    if ($location->getAddress()) {
                        $addressParts[] = $location->getAddress();
                    }
                    if ($location->getPostalCode()) {
                        $addressParts[] = $location->getPostalCode();
                    }
                    if ($location->getCity()) {
                        $addressParts[] = $location->getCity();
                    }
                    $locationAddress = implode(', ', $addressParts);
                } else {
                    $location = null;
                }
            } catch (\Exception $e) {
                $this->logger->warning('[Events Email] Could not load location: ' . $e->getMessage());
                $location = null;
            }

            // Generate QR code only for confirmed registrations
            $qrCodeImage = '';
            if ($registration->getStatus() === RegistrationInterface::STATUS_CONFIRMED) {
                $this->logger->info('[Events Email] Generating QR code for registration ID: ' . $registration->getRegistrationId());
                try {
                    // Generate attendance URL
                    $attendanceUrl = $this->urlBuilder->getUrl(
                        'events/index/attendance',
                        ['registrationId' => $registration->getRegistrationId()],
                        ['_secure' => true]
                    );
                    $qrCodeImage = $this->qrCodeGenerator->generateQrCodeImage(
                        $attendanceUrl,
                        300
                    );
                    if (empty($qrCodeImage)) {
                        $this->logger->warning('[Events Email] QR code generation returned empty string');
                        $qrCodeImage = ''; // Use empty string if QR code generation fails
                    } else {
                        $this->logger->info('[Events Email] QR code generated successfully, length: ' . strlen($qrCodeImage));
                    }
                } catch (\Exception $e) {
                    $this->logger->error('[Events Email] Exception during QR code generation: ' . $e->getMessage());
                    $this->logger->error('[Events Email] QR code exception class: ' . get_class($e));
                    $this->logger->error('[Events Email] QR code exception file: ' . $e->getFile() . ' Line: ' . $e->getLine());
                    $this->logger->error('[Events Email] QR code stack trace: ' . $e->getTraceAsString());
                    $qrCodeImage = ''; // Use empty string if QR code generation fails
                }
            } else {
                $this->logger->info('[Events Email] Skipping QR code generation for waitlist registration');
            }

            // Determine template based on status
            $templateId = $registration->getStatus() === RegistrationInterface::STATUS_CONFIRMED
                ? 'events_registration_confirmed'
                : 'events_registration_waitlist';

            // Prepare template variables
            $templateVars = [
                'registration_id' => $registration->getRegistrationId(),
                'customer_name' => $customerName,
                'meet_name' => $meet->getName(),
                'meet_date' => $meetDate,
                'meet_time' => $meetTime,
                'meet_location' => $locationName . ($locationAddress ? ' - ' . $locationAddress : ''),
                'meet_description' => $meet->getDescription() ?: '',
                'status' => $registration->getStatus() === RegistrationInterface::STATUS_CONFIRMED
                    ? __('Confirmed')->render()
                    : __('Waitlist')->render(),
                'is_admin_initiated' => $isAdminInitiated
            ];

            // Add end date variable if available
            if ($meetEndDate !== null) {
                $templateVars['meet_end_date'] = $meetEndDate;
            }
            
            // Only add QR code and calendar links for confirmed registrations
            if ($registration->getStatus() === RegistrationInterface::STATUS_CONFIRMED) {
                $templateVars['qr_code_image'] = $qrCodeImage;
                // Add calendar URLs
                $templateVars['calendar_ical_url'] = $this->calendarHelper->getIcalUrl($meet->getMeetId());
                $templateVars['calendar_google_url'] = $this->calendarHelper->getGoogleCalendarUrl($meet, $location);
            }

            // Get store and sender info
            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            // Send email
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $store->getId(),
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(
                '[Events Email] Registration email sent to ' . $customerEmail . 
                ' for registration ID ' . $registration->getRegistrationId()
            );

            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Events Email] Error sending registration email: ' . $e->getMessage());
            $this->logger->error('[Events Email] Error class: ' . get_class($e));
            $this->logger->error('[Events Email] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[Events Email] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send unregistration email
     *
     * @param RegistrationInterface $registration
     * @param MeetInterface $meet
     * @param string $customerEmail
     * @param string $customerName
     * @param bool $isAdminInitiated
     * @return bool
     */
    public function sendUnregistrationEmail(
        RegistrationInterface $registration,
        MeetInterface $meet,
        string $customerEmail,
        string $customerName,
        bool $isAdminInitiated = false
    ): bool {
        try {
            $this->inlineTranslation->suspend();

            // Format date and time - convert from UTC to store timezone
            $store = $this->storeManager->getStore();
            
            // Set store context (required for translations in cron jobs)
            $this->storeManager->setCurrentStore($store->getId());
            
            $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
            
            // Resume translation for recurring event date formatting (needed for translations)
            $this->inlineTranslation->resume();
            
            // For recurring events, use formatted date string (day of week - next occurrence (periodicity))
            $formattedRecurringDate = $this->getFormattedRecurringEventDate($meet, $store->getId());
            if ($formattedRecurringDate) {
                $meetDate = $formattedRecurringDate;
                $meetTime = ''; // Empty for recurring events since date includes time
            } else {
                // For non-recurring events, use separate date and time
                $startDate = $this->timezone->date($meet->getStartDate(), null, false);
                $meetDate = $startDate->format('d/m/Y');
                $meetTime = $startDate->format('H:i');
            }
            
            $this->inlineTranslation->suspend();

            // Format end date if recurring event has end_date (date only, no time)
            $meetEndDate = null;
            if ($isRecurring && $meet->getEndDate()) {
                $endDate = $this->timezone->date($meet->getEndDate(), null, false);
                $meetEndDate = $endDate->format('d/m/Y');
            }

            // Get location data
            $locationName = '';
            $locationAddress = '';
            try {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                if ($location && $location->getId()) {
                    $locationName = $location->getName();
                    $addressParts = [];
                    if ($location->getAddress()) {
                        $addressParts[] = $location->getAddress();
                    }
                    if ($location->getPostalCode()) {
                        $addressParts[] = $location->getPostalCode();
                    }
                    if ($location->getCity()) {
                        $addressParts[] = $location->getCity();
                    }
                    $locationAddress = implode(', ', $addressParts);
                }
            } catch (\Exception $e) {
                $this->logger->warning('[Events Email] Could not load location: ' . $e->getMessage());
            }

            // Prepare template variables
            $templateVars = [
                'customer_name' => $customerName,
                'meet_name' => $meet->getName(),
                'meet_date' => $meetDate,
                'meet_time' => $meetTime,
                'meet_location' => $locationName . ($locationAddress ? ' - ' . $locationAddress : ''),
                'is_admin_initiated' => $isAdminInitiated
            ];

            // Add end date variable if available
            if ($meetEndDate !== null) {
                $templateVars['meet_end_date'] = $meetEndDate;
            }

            // Get store and sender info
            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            // Send email
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('events_unregistration')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $store->getId(),
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(
                '[Events Email] Unregistration email sent to ' . $customerEmail . 
                ' for meet: ' . $meet->getName()
            );

            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Events Email] Error sending unregistration email: ' . $e->getMessage());
            $this->logger->error('[Events Email] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send waitlist promotion email (when status changes from waitlist to confirmed)
     *
     * @param RegistrationInterface $registration
     * @return bool
     */
    public function sendWaitlistPromotionEmail(RegistrationInterface $registration): bool
    {
        try {
            $this->inlineTranslation->suspend();

            // Get customer data
            $customer = $this->customerRepository->getById($registration->getCustomerId());
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();

            // Get meet data
            $meet = $this->meetRepository->getById($registration->getMeetId());

            // Format date and time - convert from UTC to store timezone
            $store = $this->storeManager->getStore();
            $startDate = $this->timezone->date($meet->getStartDate(), null, false);
            $meetDate = $startDate->format('d/m/Y');
            $meetTime = $startDate->format('H:i');

            // Format end date if recurring event has end_date (date only, no time)
            $meetEndDate = null;
            $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
            if ($isRecurring && $meet->getEndDate()) {
                $endDate = $this->timezone->date($meet->getEndDate(), null, false);
                $meetEndDate = $endDate->format('d/m/Y');
            }

            // Get location data
            $locationName = '';
            $locationAddress = '';
            $location = null;
            try {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                if ($location && $location->getId()) {
                    $locationName = $location->getName();
                    $addressParts = [];
                    if ($location->getAddress()) {
                        $addressParts[] = $location->getAddress();
                    }
                    if ($location->getPostalCode()) {
                        $addressParts[] = $location->getPostalCode();
                    }
                    if ($location->getCity()) {
                        $addressParts[] = $location->getCity();
                    }
                    $locationAddress = implode(', ', $addressParts);
                } else {
                    $location = null;
                }
            } catch (\Exception $e) {
                $this->logger->warning('[Events Email] Could not load location: ' . $e->getMessage());
                $location = null;
            }

            // Generate QR code (always for confirmed status)
            $this->logger->info('[Events Email] Generating QR code for promoted registration ID: ' . $registration->getRegistrationId());
            try {
                // Generate attendance URL
                $attendanceUrl = $this->urlBuilder->getUrl(
                    'events/index/attendance',
                    ['registrationId' => $registration->getRegistrationId()],
                    ['_secure' => true]
                );
                $qrCodeImage = $this->qrCodeGenerator->generateQrCodeImage(
                    $attendanceUrl,
                    300
                );
                if (empty($qrCodeImage)) {
                    $this->logger->warning('[Events Email] QR code generation returned empty string');
                    $qrCodeImage = '';
                } else {
                    $this->logger->info('[Events Email] QR code generated successfully, length: ' . strlen($qrCodeImage));
                }
            } catch (\Exception $e) {
                $this->logger->error('[Events Email] Exception during QR code generation: ' . $e->getMessage());
                $qrCodeImage = '';
            }

            // Prepare template variables
            $templateVars = [
                'registration_id' => $registration->getRegistrationId(),
                'customer_name' => $customerName,
                'meet_name' => $meet->getName(),
                'meet_date' => $meetDate,
                'meet_time' => $meetTime,
                'meet_location' => $locationName . ($locationAddress ? ' - ' . $locationAddress : ''),
                'meet_description' => $meet->getDescription() ?: '',
                'qr_code_image' => $qrCodeImage,
            ];

            // Add end date variable if available
            if ($meetEndDate !== null) {
                $templateVars['meet_end_date'] = $meetEndDate;
            }

            // Add calendar URLs
            $templateVars['calendar_ical_url'] = $this->calendarHelper->getIcalUrl($meet->getMeetId());
            $templateVars['calendar_google_url'] = $this->calendarHelper->getGoogleCalendarUrl($meet, $location);

            // Get store and sender info
            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            // Send email
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('events_waitlist_promoted')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $store->getId(),
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(
                '[Events Email] Waitlist promotion email sent to ' . $customerEmail . 
                ' for registration ID ' . $registration->getRegistrationId()
            );

            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Events Email] Error sending waitlist promotion email: ' . $e->getMessage());
            $this->logger->error('[Events Email] Error class: ' . get_class($e));
            $this->logger->error('[Events Email] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[Events Email] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send confirmed to waitlist notification email
     *
     * @param RegistrationInterface $registration
     * @return bool
     */
    public function sendConfirmedToWaitlistEmail(RegistrationInterface $registration): bool
    {
        try {
            $this->inlineTranslation->suspend();

            // Get customer data
            $customer = $this->customerRepository->getById($registration->getCustomerId());
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();

            // Get meet data
            $meet = $this->meetRepository->getById($registration->getMeetId());

            // Format date and time - convert from UTC to store timezone
            $store = $this->storeManager->getStore();
            
            // Set store context (required for translations in cron jobs)
            $this->storeManager->setCurrentStore($store->getId());
            
            $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
            
            // Resume translation for recurring event date formatting (needed for translations)
            $this->inlineTranslation->resume();
            
            // For recurring events, use formatted date string (day of week - next occurrence (periodicity))
            $formattedRecurringDate = $this->getFormattedRecurringEventDate($meet, $store->getId());
            if ($formattedRecurringDate) {
                $meetDate = $formattedRecurringDate;
                $meetTime = ''; // Empty for recurring events since date includes time
            } else {
                // For non-recurring events, use separate date and time
                $startDate = $this->timezone->date($meet->getStartDate(), null, false);
                $meetDate = $startDate->format('d/m/Y');
                $meetTime = $startDate->format('H:i');
            }
            
            $this->inlineTranslation->suspend();

            // Format end date if recurring event has end_date (date only, no time)
            $meetEndDate = null;
            if ($isRecurring && $meet->getEndDate()) {
                $endDate = $this->timezone->date($meet->getEndDate(), null, false);
                $meetEndDate = $endDate->format('d/m/Y');
            }

            // Get location data
            $locationName = '';
            $locationAddress = '';
            try {
                $location = $this->locationFactory->create()->load($meet->getLocationId());
                if ($location && $location->getId()) {
                    $locationName = $location->getName();
                    $addressParts = [];
                    if ($location->getAddress()) {
                        $addressParts[] = $location->getAddress();
                    }
                    if ($location->getPostalCode()) {
                        $addressParts[] = $location->getPostalCode();
                    }
                    if ($location->getCity()) {
                        $addressParts[] = $location->getCity();
                    }
                    $locationAddress = implode(', ', $addressParts);
                }
            } catch (\Exception $e) {
                $this->logger->warning('[Events Email] Could not load location: ' . $e->getMessage());
            }

            // Prepare template variables
            $templateVars = [
                'registration_id' => $registration->getRegistrationId(),
                'customer_name' => $customerName,
                'meet_name' => $meet->getName(),
                'meet_date' => $meetDate,
                'meet_time' => $meetTime,
                'meet_location' => $locationName . ($locationAddress ? ' - ' . $locationAddress : ''),
                'meet_description' => $meet->getDescription() ?: '',
            ];

            // Add end date variable if available
            if ($meetEndDate !== null) {
                $templateVars['meet_end_date'] = $meetEndDate;
            }

            // Get store and sender info
            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            // Send email
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('events_confirmed_to_waitlist')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $store->getId(),
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(
                '[Events Email] Confirmed to waitlist email sent to ' . $customerEmail . 
                ' for registration ID ' . $registration->getRegistrationId()
            );

            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Events Email] Error sending confirmed to waitlist email: ' . $e->getMessage());
            $this->logger->error('[Events Email] Error class: ' . get_class($e));
            $this->logger->error('[Events Email] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[Events Email] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send reminder email
     *
     * @param RegistrationInterface $registration
     * @param int $daysBefore
     * @return bool
     */
    public function sendReminderEmail(RegistrationInterface $registration, int $daysBefore): bool
    {
        try {
            $this->inlineTranslation->suspend();

            // Set area code if not already set (required for URL generation in cron jobs)
            try {
                $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
            } catch (\Exception $e) {
                // Area code already set, ignore
            }

            // Get meet
            $meet = $this->meetRepository->getById($registration->getMeetId());

            // Get customer
            $customer = $this->customerRepository->getById($registration->getCustomerId());
            $customerEmail = $customer->getEmail();
            $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
            if (empty($customerName)) {
                $customerName = $customerEmail;
            }

            // Generate unsubscribe code if not exists
            $unsubscribeCode = $registration->getUnsubscribeCode();
            if (empty($unsubscribeCode)) {
                $unsubscribeCode = bin2hex(random_bytes(16));
                $registration->setUnsubscribeCode($unsubscribeCode);
                // Note: Caller must save the registration after this method returns
            }

            // Build unsubscribe URL
            $unsubscribeUrl = $this->urlBuilder->getUrl(
                'events/index/unsubscribe',
                ['code' => $unsubscribeCode]
            );

            // Get location
            $location = $this->locationFactory->create()->load($meet->getLocationId());
            $locationName = $location->getName();
            $locationAddress = $location->getAddress();

            // Get store from customer's website to ensure correct locale
            try {
                $websiteId = $customer->getWebsiteId();
                $store = $this->storeManager->getStore();
                // Try to get store from website
                if ($websiteId) {
                    $website = $this->storeManager->getWebsite($websiteId);
                    $storeIds = $website->getStoreIds();
                    if (!empty($storeIds)) {
                        $store = $this->storeManager->getStore(reset($storeIds));
                    }
                }
            } catch (\Exception $e) {
                // Fallback to default store
                $store = $this->storeManager->getStore();
            }
            
            // Set store context (required for translations in cron jobs)
            $this->storeManager->setCurrentStore($store->getId());
            
            // Resume translation before formatting dates (needed for recurring events with translations)
            $this->inlineTranslation->resume();
            
            // Format date and time - convert from UTC to store timezone
            $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
            
            // For recurring events, use formatted date string (day of week - next occurrence (periodicity))
            $formattedRecurringDate = $this->getFormattedRecurringEventDate($meet, $store->getId());
            if ($formattedRecurringDate) {
                $meetDate = $formattedRecurringDate;
                $meetTime = ''; // Empty for recurring events since date includes time
            } else {
                // For non-recurring events, use separate date and time
                $startDate = $this->timezone->date($meet->getStartDate(), null, false);
                $meetDate = $startDate->format('d/m/Y');
                $meetTime = $startDate->format('H:i');
            }

            // Format end date if recurring event has end_date (date only, no time)
            $meetEndDate = null;
            if ($isRecurring && $meet->getEndDate()) {
                $endDate = $this->timezone->date($meet->getEndDate(), null, false);
                $meetEndDate = $endDate->format('d/m/Y');
            }

            // Translate reminder message (using day(s) to handle both singular and plural)
            $reminderMessage = __('This is a reminder that you have an event coming up in %1 day(s).', $daysBefore)->render();
            
            $this->inlineTranslation->suspend();

            // Prepare template variables
            $templateVars = [
                'registration_id' => $registration->getRegistrationId(),
                'customer_name' => $customerName,
                'meet_name' => $meet->getName(),
                'meet_date' => $meetDate,
                'meet_time' => $meetTime,
                'meet_location' => $locationName . ($locationAddress ? ' - ' . $locationAddress : ''),
                'meet_description' => $meet->getDescription() ?: '',
                'days_before' => $daysBefore,
                'reminder_message' => $reminderMessage,
                'unsubscribe_url' => $unsubscribeUrl,
            ];

            // Add end date variable if available
            if ($meetEndDate !== null) {
                $templateVars['meet_end_date'] = $meetEndDate;
            }

            // Get store and sender info
            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            // Send email
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('events_reminder')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $store->getId(),
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(
                '[Events Email] Reminder email sent to ' . $customerEmail . 
                ' for registration ID ' . $registration->getRegistrationId() . 
                ' (' . $daysBefore . ' days before event)'
            );

            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Events Email] Error sending reminder email: ' . $e->getMessage());
            $this->logger->error('[Events Email] Error class: ' . get_class($e));
            $this->logger->error('[Events Email] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[Events Email] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get formatted date string for recurring events (day of week - next occurrence (periodicity))
     *
     * @param MeetInterface $meet
     * @param int|null $storeId Store ID for translations
     * @return string|null
     */
    protected function getFormattedRecurringEventDate(MeetInterface $meet, ?int $storeId = null): ?string
    {
        $isRecurring = $meet->getRecurrenceType() !== \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_NONE;
        if (!$isRecurring) {
            return null;
        }

        // Convert UTC date to store timezone (use current store that was set by caller)
        $store = $this->storeManager->getStore(); // This should return the current store set by setCurrentStore()
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
        $timezoneObj = new \DateTimeZone($timezone);

        // Get day of week from start date
        $startDate = new \DateTime($meet->getStartDate(), new \DateTimeZone('UTC'));
        $startDate->setTimezone($timezoneObj);
        
        $dayNumber = (int) $startDate->format('w'); // 0 = Sunday, 6 = Saturday

        // Calculate next occurrence
        $now = new \DateTime('now', $timezoneObj);
        $recurrenceType = $meet->getRecurrenceType();
        $nextDate = clone $startDate;

        if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            // Biweekly (every 15 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+15 days');
            }
        } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            // Weekly (every 7 days)
            while ($nextDate <= $now) {
                $nextDate->modify('+7 days');
            }
        } else {
            return null;
        }

        // Format next occurrence date
        $formattedNextDate = $nextDate->format('d/m/Y H:i');

        // Get day of week using PHP's IntlDateFormatter for proper locale support
        $dayOfWeekText = null;
        if ($storeId !== null) {
            try {
                $store = $this->storeManager->getStore($storeId);
                $locale = $store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);
                
                // Use IntlDateFormatter to get localized day name
                if (class_exists('IntlDateFormatter')) {
                    $formatter = new \IntlDateFormatter(
                        $locale,
                        \IntlDateFormatter::NONE,
                        \IntlDateFormatter::NONE,
                        $timezoneObj,
                        \IntlDateFormatter::GREGORIAN,
                        'EEEE' // Full day name
                    );
                    $dayOfWeekText = $formatter->format($startDate);
                } else {
                    // Fallback to English if Intl extension not available
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $dayOfWeekText = $days[$dayNumber] ?? null;
                }
            } catch (\Exception $e) {
                // Fallback to English
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $dayOfWeekText = $days[$dayNumber] ?? null;
            }
        } else {
            // Fallback to English
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $dayOfWeekText = $days[$dayNumber] ?? null;
        }
        
        if (!$dayOfWeekText) {
            return null;
        }

        // Get periodicity label - use store context for translations
        $originalStore = null;
        if ($storeId !== null) {
            try {
                $originalStore = $this->storeManager->getStore()->getId();
                $this->storeManager->setCurrentStore($storeId);
            } catch (\Exception $e) {
                // Ignore if store setting fails
            }
        }
        
        if ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_QUINCENAL) {
            $periodicity = __('Biweekly');
        } elseif ($recurrenceType === \Zaca\Events\Api\Data\MeetInterface::RECURRENCE_TYPE_SEMANAL) {
            $periodicity = __('Weekly');
        } else {
            $periodicity = null;
        }

        // Render periodicity translation
        $periodicityText = $periodicity ? $periodicity->render() : '';
        
        // Restore original store if we changed it
        if ($originalStore !== null) {
            try {
                $this->storeManager->setCurrentStore($originalStore);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if ($periodicityText) {
            return $dayOfWeekText . ' - ' . $formattedNextDate . ' (' . $periodicityText . ')';
        }

        return $dayOfWeekText . ' - ' . $formattedNextDate;
    }
}

