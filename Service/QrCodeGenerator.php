<?php
/**
 * Zacatrus Events QR Code Generator Service
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Service;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Psr\Log\LoggerInterface;

class QrCodeGenerator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param string $data
     * @param int $size
     * @return \Endroid\QrCode\Writer\Result\ResultInterface
     */
    private function buildResult(string $data, int $size)
    {
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 10
        );

        return (new PngWriter())->write($qrCode);
    }

    /**
     * Generate QR code image as base64 data URI
     *
     * @param string $data Data to encode in QR code
     * @param int $size QR code size in pixels
     * @return string Base64 data URI
     */
    public function generateQrCodeImage(string $data, int $size = 300): string
    {
        try {
            return $this->buildResult($data, $size)->getDataUri();
        } catch (\Throwable $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code: ' . $e->getMessage());
            $this->logger->error('[QR Code Generator] Error class: ' . get_class($e));
            $this->logger->error('[QR Code Generator] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[QR Code Generator] Stack trace: ' . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Generate QR code image as binary PNG data
     *
     * @param string $data Data to encode in QR code
     * @param int $size QR code size in pixels
     * @return string Binary PNG data
     */
    public function generateQrCodeBinary(string $data, int $size = 300): string
    {
        try {
            return $this->buildResult($data, $size)->getString();
        } catch (\Throwable $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code binary: ' . $e->getMessage());
            $this->logger->error('[QR Code Generator] Error class: ' . get_class($e));
            $this->logger->error('[QR Code Generator] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[QR Code Generator] Stack trace: ' . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Generate QR code and save to file
     *
     * @param string $data Data to encode in QR code
     * @param string $filePath Full path to save the file
     * @param int $size QR code size in pixels
     * @return bool Success
     */
    public function generateQrCodeFile(string $data, string $filePath, int $size = 300): bool
    {
        try {
            $this->buildResult($data, $size)->saveToFile($filePath);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code file: ' . $e->getMessage());
            return false;
        }
    }
}
