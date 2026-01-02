<?php
namespace Zacatrus\Events\Controller\Adminhtml\League;

use Magento\Backend\App\Action;
use Zacatrus\Events\Model\LeagueFactory;

class Delete extends Action
{
    /**
     * @var LeagueFactory
     */
    protected $leagueFactory;

    /**
     * @param Action\Context $context
     * @param LeagueFactory $leagueFactory
     */
    public function __construct(
        Action\Context $context,
        LeagueFactory $leagueFactory
    ) {
        parent::__construct($context);
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
     * Delete league action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('league_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->leagueFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The league has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['league_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a league to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}

