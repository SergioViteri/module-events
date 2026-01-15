<?php
/**
 * Zacatrus Events QR Code Controller (Serve QR Code Image)
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Zaca\Events\Api\RegistrationRepositoryInterface;
use Zaca\Events\Service\QrCodeGenerator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Zaca\Events\Helper\Data as EventsHelper;

class QrCode extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RegistrationRepositoryInterface
     */
    protected $registrationRepository;

    /**
     * @var QrCodeGenerator
     */
    protected $qrCodeGenerator;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var EventsHelper
     */
    protected $eventsHelper;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param RegistrationRepositoryInterface $registrationRepository
     * @param QrCodeGenerator $qrCodeGenerator
     * @param RawFactory $resultRawFactory
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        RegistrationRepositoryInterface $registrationRepository,
        QrCodeGenerator $qrCodeGenerator,
        RawFactory $resultRawFactory,
        LoggerInterface $logger,
        UrlInterface $urlBuilder,
        EventsHelper $eventsHelper
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->registrationRepository = $registrationRepository;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->resultRawFactory = $resultRawFactory;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->eventsHelper = $eventsHelper;
    }

    /**
     * Execute action
     *
     * @return Raw|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Check if module is enabled
        $isEnabled = $this->scopeConfig->getValue(
            'zaca_events/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        $registrationId = (int) $this->getRequest()->getParam('registrationId');
        
        if (!$registrationId) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }

        try {
            $registration = $this->registrationRepository->getById($registrationId);
            
            if (!$registration || !$registration->getRegistrationId()) {
                throw new NoSuchEntityException(__('Registration not found.'));
            }

            // Generate attendance URL
            $routePath = $this->eventsHelper->getRoutePath();
            $attendanceUrl = $this->urlBuilder->getUrl(
                $routePath . '/index/attendance',
                ['registrationId' => $registration->getRegistrationId()],
                ['_secure' => true]
            );

            // Generate QR code binary
            $qrCodeBinary = $this->qrCodeGenerator->generateQrCodeBinary($attendanceUrl, 300);
            
            if (empty($qrCodeBinary)) {
                $this->logger->error('[QR Code Controller] Failed to generate QR code for registration ID: ' . $registrationId);
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
            }

            // Return PNG image
            $result = $this->resultRawFactory->create();
            $result->setHeader('Content-Type', 'image/png');
            $result->setHeader('Content-Disposition', 'inline; filename="qrcode.png"');
            $result->setHeader('Cache-Control', 'public, max-age=3600');
            $result->setHeader('Content-Length', strlen($qrCodeBinary));
            $result->setContents($qrCodeBinary);

            return $result;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('[QR Code Controller] Registration not found: ' . $registrationId);
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        } catch (\Exception $e) {
            $this->logger->error('[QR Code Controller] Error generating QR code: ' . $e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath($this->eventsHelper->getRoutePath());
        }
    }
}
