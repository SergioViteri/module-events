<?php
namespace Zacatrus\Events\Controller\Adminhtml\League;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Zacatrus\Events\Model\LeagueFactory;

class Save extends Action
{
    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @var LeagueFactory
     */
    protected $leagueFactory;

    /**
     * @param Action\Context $context
     * @param Session $adminSession
     * @param LeagueFactory $leagueFactory
     */
    public function __construct(
        Action\Context $context,
        Session $adminSession,
        LeagueFactory $leagueFactory
    ) {
        parent::__construct($context);
        $this->adminSession = $adminSession;
        $this->leagueFactory = $leagueFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zacatrus_Events::leagues');
    }

    /**
     * Save league action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->leagueFactory->create();
            $id = $this->getRequest()->getParam('league_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The league has been saved.'));
                $this->adminSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['league_id' => $model->getId(), '_current' => true]
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
                    __('Something went wrong while saving the league.')
                );
            }

            $this->adminSession->setFormData($data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['league_id' => $this->getRequest()->getParam('league_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

