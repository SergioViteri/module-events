<?php
/**
 * Zacatrus Events League Model
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Model;

use Zacatrus\Events\Model\ResourceModel\League as LeagueResourceModel;
use Zacatrus\Events\Api\Data\LeagueInterface;
use Magento\Framework\Model\AbstractModel;

class League extends AbstractModel implements LeagueInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LeagueResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getLeagueId()
    {
        return $this->getData(self::LEAGUE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLeagueId($leagueId)
    {
        return $this->setData(self::LEAGUE_ID, $leagueId);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setStartDate($startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * @inheritdoc
     */
    public function getEndDate()
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setEndDate($endDate)
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

