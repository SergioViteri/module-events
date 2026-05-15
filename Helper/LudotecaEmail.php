<?php
/**
 * Sends ludoteca booking emails (confirmation, cancellation).
 *
 * Kept separate from Helper\Email to avoid coupling the events email flow
 * (which inflates Helper\Email with meet/registration concerns) to the new
 * ludoteca data shapes.
 */

namespace Zaca\Events\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zaca\Events\Api\TableBookingRepositoryInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Model\LocationFactory;

class LudotecaEmail extends AbstractHelper
{
    private StateInterface $inlineTranslation;
    private Escaper $escaper;
    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private CustomerRepositoryInterface $customerRepository;
    private TableBookingRepositoryInterface $bookings;
    private LocationFactory $locationFactory;
    private UrlInterface $urlBuilder;
    private TimezoneInterface $timezone;
    private EventsHelper $helper;
    private State $appState;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        TableBookingRepositoryInterface $bookings,
        LocationFactory $locationFactory,
        UrlInterface $urlBuilder,
        TimezoneInterface $timezone,
        EventsHelper $helper,
        State $appState
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->bookings = $bookings;
        $this->locationFactory = $locationFactory;
        $this->urlBuilder = $urlBuilder;
        $this->timezone = $timezone;
        $this->helper = $helper;
        $this->appState = $appState;
        $this->logger = $context->getLogger();
    }

    public function sendBookingConfirmation(int $bookingId): bool
    {
        try {
            $booking = $this->bookings->getById($bookingId);
            $customer = $this->customerRepository->getById($booking->getCustomerId());
            $location = $this->locationFactory->create()->load($booking->getLocationId());

            $store = $this->storeManager->getStore();
            $this->ensureFrontendArea();

            $this->inlineTranslation->suspend();

            $base = rtrim($this->urlBuilder->getBaseUrl(['_secure' => true]), '/');
            $qrUrl = $base . $this->helper->getLudotecaPublicUrl('qrcode', ['id' => $bookingId]);
            $cancelUrl = $base . $this->helper->getLudotecaPublicUrl('cancel', [
                'code' => (string) $booking->getUnsubscribeCode(),
            ]);

            $vars = [
                'booking_id' => $booking->getBookingId(),
                'customer_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()) ?: $customer->getEmail(),
                'location_name' => (string) $location->getName(),
                'location_address' => trim((string) $location->getAddress() . ', ' . (string) $location->getCity(), ', '),
                'booking_date' => $this->formatDateHuman($booking->getBookingDate()),
                'slots_text' => $this->renderSlotsHtml($bookingId),
                'qr_code_url' => $qrUrl,
                'cancel_url' => $cancelUrl,
            ];

            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            $this->transportBuilder
                ->setTemplateIdentifier('ludoteca_booking_confirmed')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($vars)
                ->setFrom($sender)
                ->addTo($customer->getEmail(), $vars['customer_name'])
                ->getTransport()
                ->sendMessage();

            $this->inlineTranslation->resume();
            return true;
        } catch (\Throwable $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Ludoteca Email] Confirmation error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendBookingReminder(int $bookingId, int $daysBefore): bool
    {
        try {
            $booking = $this->bookings->getById($bookingId);
            $customer = $this->customerRepository->getById($booking->getCustomerId());
            $location = $this->locationFactory->create()->load($booking->getLocationId());

            $store = $this->storeManager->getStore();
            $this->ensureFrontendArea();

            $this->inlineTranslation->suspend();

            $base = rtrim($this->urlBuilder->getBaseUrl(['_secure' => true]), '/');
            $qrUrl = $base . $this->helper->getLudotecaPublicUrl('qrcode', ['id' => $bookingId]);
            $cancelUrl = $base . $this->helper->getLudotecaPublicUrl('cancel', [
                'code' => (string) $booking->getUnsubscribeCode(),
            ]);

            $vars = [
                'booking_id' => $booking->getBookingId(),
                'customer_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()) ?: $customer->getEmail(),
                'location_name' => (string) $location->getName(),
                'location_address' => trim((string) $location->getAddress() . ', ' . (string) $location->getCity(), ', '),
                'booking_date' => $this->formatDateHuman($booking->getBookingDate()),
                'slots_text' => $this->renderSlotsHtml($bookingId),
                'qr_code_url' => $qrUrl,
                'cancel_url' => $cancelUrl,
                'days_before' => $daysBefore,
            ];

            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            $this->transportBuilder
                ->setTemplateIdentifier('ludoteca_booking_reminder')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($vars)
                ->setFrom($sender)
                ->addTo($customer->getEmail(), $vars['customer_name'])
                ->getTransport()
                ->sendMessage();

            $this->inlineTranslation->resume();
            return true;
        } catch (\Throwable $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Ludoteca Email] Reminder error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendBookingCancellation(int $bookingId): bool
    {
        try {
            $booking = $this->bookings->getById($bookingId);
            $customer = $this->customerRepository->getById($booking->getCustomerId());
            $location = $this->locationFactory->create()->load($booking->getLocationId());
            $store = $this->storeManager->getStore();
            $this->ensureFrontendArea();

            $this->inlineTranslation->suspend();

            $vars = [
                'booking_id' => $booking->getBookingId(),
                'customer_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()) ?: $customer->getEmail(),
                'location_name' => (string) $location->getName(),
                'booking_date' => $this->formatDateHuman($booking->getBookingDate()),
            ];

            $sender = [
                'name' => $this->escaper->escapeHtml($store->getStoreName()),
                'email' => $store->getStoreEmail() ?: 'noreply@zacatrus.es',
            ];

            $this->transportBuilder
                ->setTemplateIdentifier('ludoteca_booking_cancelled')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($vars)
                ->setFrom($sender)
                ->addTo($customer->getEmail(), $vars['customer_name'])
                ->getTransport()
                ->sendMessage();

            $this->inlineTranslation->resume();
            return true;
        } catch (\Throwable $e) {
            $this->inlineTranslation->resume();
            $this->logger->error('[Ludoteca Email] Cancellation error: ' . $e->getMessage());
            return false;
        }
    }

    private function renderSlotsHtml(int $bookingId): string
    {
        try {
            $items = [];
            foreach ($this->bookings->getSlots($bookingId) as $slot) {
                $start = substr((string) $slot->getData('start_time'), 0, 5);
                $end = substr((string) $slot->getData('end_time'), 0, 5);
                $tables = (int) $slot->getTablesCount();
                $items[] = sprintf(
                    '%s – %s · %d %s',
                    $start,
                    $end,
                    $tables,
                    __('mesa(s)')->render()
                );
            }
            return implode('<br/>', array_map([$this->escaper, 'escapeHtml'], $items));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatDateHuman(string $ymd): string
    {
        try {
            return (new \DateTimeImmutable($ymd))->format('d/m/Y');
        } catch (\Throwable $e) {
            return $ymd;
        }
    }

    private function ensureFrontendArea(): void
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        } catch (\Throwable $e) {
            // Already set, ignore.
        }
    }
}
