<?php
/**
 * Zacatrus Events Admin Participant Export CSV Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Participant;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Zaca\Events\Model\RegistrationFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Magento\Framework\View\LayoutFactory;

class ExportCsv extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RegistrationFactory $registrationFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        RegistrationFactory $registrationFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->registrationFactory = $registrationFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::participants');
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            // Create grid block to reuse its collection and filtering logic
            $layout = $this->layoutFactory->create();
            $gridBlock = $layout->createBlock(
                \Zaca\Events\Block\Adminhtml\Participant\Grid::class,
                'participant.grid.export'
            );
            
            // Prepare the grid (this will apply all filters from session)
            $gridBlock->toHtml(); // This triggers collection preparation with filters
            
            // Get the filtered collection
            $collection = $gridBlock->getCollection();
            
            // Remove pagination for export
            $collection->setPageSize(false);
            $collection->setCurPage(1);

            // Build CSV content
            $csv = [];
            $csv[] = [
                'ID',
                'Customer Name',
                'Meet Name',
                'Meet ID',
                'Customer ID',
                'Status',
                'Attendance Count',
                'Registration Date',
                'Created At'
            ];

            foreach ($collection as $item) {
                $csv[] = [
                    $item->getRegistrationId(),
                    $item->getData('customer_name') ?: '',
                    $item->getData('meet_name') ?: '',
                    $item->getMeetId(),
                    $item->getCustomerId(),
                    $item->getStatus(),
                    $item->getAttendanceCount(),
                    $item->getRegistrationDate(),
                    $item->getCreatedAt()
                ];
            }

            // Convert to CSV string
            $content = '';
            foreach ($csv as $row) {
                $content .= $this->arrayToCsv($row) . "\n";
            }

            // Generate filename with timestamp
            $filename = 'participants_' . date('Y-m-d_His') . '.csv';

            return $this->fileFactory->create(
                $filename,
                $content,
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        } catch (\Exception $e) {
            $this->logger->error('[Participant Export] Error: ' . $e->getMessage());
            $this->messageManager->addError(__('Error exporting participants: %1', $e->getMessage()));
            return $this->_redirect('*/*/index');
        }
    }

    /**
     * Convert array to CSV line
     *
     * @param array $data
     * @return string
     */
    protected function arrayToCsv(array $data)
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $data);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return rtrim($csv, "\n");
    }
}

