<?php
/**
 * Carnet → asistencia: cuando el empleado escanea el QR del carnet de socio
 * (no el QR específico de un evento), este controller localiza al cliente por
 * el barcode CLT<HEX> y, dada la tienda donde está el empleado (location_id en
 * sesión), busca los registros del cliente para hoy en esa tienda.
 *
 *   - 1 coincidencia → redirige a /events/index/attendance?registrationId=…
 *     y deja al flujo estándar registrar la asistencia.
 *   - 0 coincidencias → mensaje "no hay eventos hoy".
 *   - >1 coincidencias → picker para que el empleado elija.
 *
 * Si no hay location_id en sesión, primero pide el código de tienda (se
 * valida contra cualquier location existente, no contra un meet concreto, ya
 * que aún no sabemos a qué meet va el cliente).
 */
namespace Zaca\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Zaca\Events\Helper\Data as EventsHelper;
use Zaca\Events\Service\AttendanceValidator;

/**
 * CSRF: this endpoint is only meaningful when used by store staff at a store,
 * and the location_code itself is the auth factor. Form-key validation adds no
 * security and breaks the scan-and-enter UX, so it is opted out via
 * CsrfAwareActionInterface.
 */
class CarnetAttend extends Action implements CsrfAwareActionInterface
{
    const CUSTOMER_COLOR_ATTRIBUTE = 'member_color_hex';

    private $resultPageFactory;
    private $session;
    private $registry;
    private $logger;
    private $eventsHelper;
    private $attendanceValidator;
    private $resource;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SessionManager $session,
        Registry $registry,
        LoggerInterface $logger,
        EventsHelper $eventsHelper,
        AttendanceValidator $attendanceValidator,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $session;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->eventsHelper = $eventsHelper;
        $this->attendanceValidator = $attendanceValidator;
        $this->resource = $resource;
    }

    public function execute()
    {
        $barcode = strtoupper((string) $this->getRequest()->getParam('b', ''));
        if (!preg_match('/^CLT[0-9A-F]{6}$/', $barcode)) {
            $this->messageManager->addErrorMessage(__('Carnet no válido.'));
            return $this->redirectToRoot();
        }

        if (!$this->session->isSessionExists()) {
            $this->session->start();
        }

        // Step 1: location code in session, or accept it via POST.
        if ($this->getRequest()->isPost()) {
            $locationCode = (string) $this->getRequest()->getParam('location_code', '');
            if ($locationCode !== '') {
                $locId = $this->attendanceValidator->findLocationIdByCode($locationCode);
                if ($locId) {
                    $this->session->setData('zaca_events_location_id', $locId);
                    $this->session->setData('zaca_events_location_code', $locationCode);
                    $this->messageManager->addSuccessMessage(__('Tienda validada.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Código de tienda no válido.'));
                }
            }
            return $this->resultRedirectFactory->create()->setPath(
                $this->eventsHelper->getRoutePath() . '/index/carnetAttend',
                ['b' => $barcode]
            );
        }

        $locationId = (int) $this->session->getData('zaca_events_location_id');

        $customerId = $this->findCustomerIdByBarcode($barcode);
        if (!$customerId) {
            $this->messageManager->addErrorMessage(__('No se encuentra ningún cliente con ese carnet.'));
            return $this->redirectToRoot();
        }

        $customerName = $this->fetchCustomerName($customerId);

        // Need a tienda set. Render the "ask for code" view scoped to the carnet flow.
        if (!$locationId) {
            $this->registry->register('carnet_barcode', $barcode);
            $this->registry->register('carnet_customer_id', $customerId);
            $this->registry->register('carnet_customer_name', $customerName);
            $this->registry->register('carnet_state', 'awaiting_location');
            $page = $this->resultPageFactory->create();
            $page->getConfig()->getTitle()->set(__('Confirmar asistencia · Carnet'));
            return $page;
        }

        $matches = $this->attendanceValidator->findRegistrationsForCustomerTodayAtLocation(
            $customerId,
            $locationId,
            new \DateTime()
        );

        if (count($matches) === 0) {
            $this->registry->register('carnet_barcode', $barcode);
            $this->registry->register('carnet_customer_name', $customerName);
            $this->registry->register('carnet_state', 'no_match');
            $page = $this->resultPageFactory->create();
            $page->getConfig()->getTitle()->set(__('Confirmar asistencia · Carnet'));
            return $page;
        }

        if (count($matches) === 1) {
            // Single hit: hand off to the canonical attendance flow.
            $registration = $matches[0]['registration'];
            return $this->resultRedirectFactory->create()->setPath(
                $this->eventsHelper->getRoutePath() . '/index/attendance',
                ['registrationId' => $registration->getRegistrationId()]
            );
        }

        // Multiple: render picker.
        $this->registry->register('carnet_barcode', $barcode);
        $this->registry->register('carnet_customer_name', $customerName);
        $this->registry->register('carnet_matches', $matches);
        $this->registry->register('carnet_state', 'pick');
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->set(__('Confirmar asistencia · Carnet'));
        return $page;
    }

    private function redirectToRoot()
    {
        return $this->resultRedirectFactory->create()
            ->setPath($this->eventsHelper->getRoutePath());
    }

    /**
     * Resolve the Magento customer holding the given CLT<HEX> barcode by
     * matching against the member_color_hex customer attribute.
     */
    private function findCustomerIdByBarcode(string $barcode): ?int
    {
        $hex = '#' . substr($barcode, 3);
        $connection = $this->resource->getConnection();
        $eav = $this->resource->getTableName('eav_attribute');
        $entityType = $this->resource->getTableName('eav_entity_type');
        $varchar = $this->resource->getTableName('customer_entity_varchar');

        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from($eav, ['attribute_id'])
                ->where('attribute_code = ?', self::CUSTOMER_COLOR_ATTRIBUTE)
                ->where('entity_type_id = (?)',
                    $connection->select()->from($entityType, ['entity_type_id'])
                        ->where('entity_type_code = ?', 'customer'))
                ->limit(1)
        );
        if (!$attributeId) {
            return null;
        }
        $customerId = $connection->fetchOne(
            $connection->select()
                ->from($varchar, ['entity_id'])
                ->where('attribute_id = ?', $attributeId)
                ->where('UPPER(value) = ?', $hex)
                ->limit(1)
        );
        return $customerId ? (int) $customerId : null;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function fetchCustomerName(int $customerId): string
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('customer_entity');
        $row = $connection->fetchRow(
            $connection->select()
                ->from($table, ['firstname', 'lastname', 'email'])
                ->where('entity_id = ?', $customerId)
        );
        if (!$row) {
            return '';
        }
        $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        return $name !== '' ? $name : (string) ($row['email'] ?? '');
    }
}
