<?php
/**
 * Zacatrus Events Admin Meet Save Controller
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Controller\Adminhtml\Meet;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zaca\Events\Model\MeetFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var MeetFactory
     */
    protected $meetFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param MeetFactory $meetFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        MeetFactory $meetFactory,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->meetFactory = $meetFactory;
        $this->timezone = $timezone;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_Events::meets');
    }

    /**
     * Save meet action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->meetFactory->create();
            $id = $this->getRequest()->getParam('meet_id');

            if ($id) {
                $model->load($id);
            }

            // Convert empty theme_id to NULL for optional foreign key
            if (isset($data['theme_id']) && $data['theme_id'] === '') {
                $data['theme_id'] = null;
            }

            // Convert dates from admin timezone (form input) to UTC for storage
            // The form displays dates in admin timezone, but we need to store in UTC
            if (isset($data['start_date']) && !empty($data['start_date'])) {
                // Parse date as if it's in admin timezone, then convert to UTC
                $date = new \DateTime($data['start_date'], new \DateTimeZone($this->timezone->getConfigTimezone()));
                $date->setTimezone(new \DateTimeZone('UTC'));
                $data['start_date'] = $date->format('Y-m-d H:i:s');
            }
            if (isset($data['end_date']) && !empty($data['end_date'])) {
                // Parse date as if it's in admin timezone, then convert to UTC
                $date = new \DateTime($data['end_date'], new \DateTimeZone($this->timezone->getConfigTimezone()));
                $date->setTimezone(new \DateTimeZone('UTC'));
                $data['end_date'] = $date->format('Y-m-d H:i:s');
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The meet has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['meet_id' => $model->getId(), '_current' => true]
                    );
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while saving the meet.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['meet_id' => $this->getRequest()->getParam('meet_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

