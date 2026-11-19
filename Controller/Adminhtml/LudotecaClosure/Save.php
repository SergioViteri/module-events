<?php

namespace Zaca\Events\Controller\Adminhtml\LudotecaClosure;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\Ludoteca\ClosureFactory;
use Zaca\Events\Model\ResourceModel\Ludoteca\Closure as ClosureResource;

class Save extends Action
{
    private Session $adminSession;
    private ClosureFactory $factory;
    private ClosureResource $resource;

    public function __construct(
        Action\Context $context,
        Session $adminSession,
        ClosureFactory $factory,
        ClosureResource $resource
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->factory = $factory;
        $this->resource = $resource;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::ludoteca_closures');
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $model = $this->factory->create();
        $id = $this->getRequest()->getParam('closure_id');
        if ($id) {
            $model->load($id);
        }

        // Normalise: empty location → every ludoteca (NULL)
        if (!isset($data['location_id']) || $data['location_id'] === '') {
            $data['location_id'] = null;
        }
        $data['reason'] = isset($data['reason']) ? trim((string) $data['reason']) : '';
        if ($data['reason'] === '') {
            $data['reason'] = null;
        }
        $model->setData($data);

        try {
            $this->validateDates((string) ($data['start_date'] ?? ''), (string) ($data['end_date'] ?? ''));
            $model->save();
            $this->messageManager->addSuccessMessage(__('Closure saved.'));
            $this->warnAboutExistingBookings($model);
            $this->adminSession->setFormData(false);
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['closure_id' => $model->getId(), '_current' => true]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving closure: %1', $e->getMessage()));
        }

        $this->adminSession->setFormData($data);
        return $resultRedirect->setPath('*/*/edit', ['closure_id' => $id]);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateDates(string $startDate, string $endDate): void
    {
        foreach ([$startDate, $endDate] as $value) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException((string) __('Enter both dates as YYYY-MM-DD.'));
            }
        }
        if ($endDate < $startDate) {
            throw new \InvalidArgumentException((string) __('The last closed day cannot be before the first one.'));
        }
    }

    /**
     * Closing a period does not cancel bookings already made inside it.
     */
    private function warnAboutExistingBookings(\Magento\Framework\Model\AbstractModel $model): void
    {
        if (!$model->getData('is_active')) {
            return;
        }
        $locationId = $model->getData('location_id');
        $count = $this->resource->countConfirmedBookings(
            $locationId === null ? null : (int) $locationId,
            (string) $model->getData('start_date'),
            (string) $model->getData('end_date')
        );
        if ($count > 0) {
            $this->messageManager->addWarningMessage(__(
                'There are %1 confirmed booking(s) inside this period. They have not been cancelled: review them in Ludoteca Bookings.',
                $count
            ));
        }
    }
}
