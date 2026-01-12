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
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
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
     * Generate QR code image as base64 data URI
     *
     * @param string $data Data to encode in QR code
     * @param int $size QR code size in pixels
     * @return string Base64 data URI
     */
    public function generateQrCodeImage(string $data, int $size = 300): string
    {
        try {
            $writer = new PngWriter();
            
            $result = $writer->write(
                \Endroid\QrCode\QrCode::create($data)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                    ->setSize($size)
                    ->setMargin(10)
            );

            // Get the data URI
            $dataUri = $result->getDataUri();
            
            return $dataUri;
        } catch (\Throwable $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code: ' . $e->getMessage());
            $this->logger->error('[QR Code Generator] Error class: ' . get_class($e));
            $this->logger->error('[QR Code Generator] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[QR Code Generator] Stack trace: ' . $e->getTraceAsString());
            // Return empty string on error
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
            $writer = new PngWriter();
            
            $result = $writer->write(
                \Endroid\QrCode\QrCode::create($data)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                    ->setSize($size)
                    ->setMargin(10)
            );

            // Get the binary string
            $binary = $result->getString();
            
            return $binary;
        } catch (\Throwable $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code binary: ' . $e->getMessage());
            $this->logger->error('[QR Code Generator] Error class: ' . get_class($e));
            $this->logger->error('[QR Code Generator] Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            $this->logger->error('[QR Code Generator] Stack trace: ' . $e->getTraceAsString());
            // Return empty string on error
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
            $writer = new PngWriter();
            
            $result = $writer->write(
                \Endroid\QrCode\QrCode::create($data)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                    ->setSize($size)
                    ->setMargin(10)
            );

            // Save to file
            $result->saveToFile($filePath);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('[QR Code Generator] Error generating QR code file: ' . $e->getMessage());
            return false;
        }
    }
}

