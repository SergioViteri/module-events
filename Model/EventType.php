<?php
/**
 * Zacatrus Events EventType Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\EventType as EventTypeResourceModel;
use Magento\Framework\Model\AbstractModel;

class EventType extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(EventTypeResourceModel::class);
    }

    /**
     * Get Event Type ID
     *
     * @return int|null
     */
    public function getEventTypeId()
    {
        return $this->getData('event_type_id');
    }

    /**
     * Set Event Type ID
     *
     * @param int $eventTypeId
     * @return $this
     */
    public function setEventTypeId($eventTypeId)
    {
        return $this->setData('event_type_id', $eventTypeId);
    }

    /**
     * Get Code
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->getData('code');
    }

    /**
     * Set Code
     *
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        return $this->setData('code', $code);
    }

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData('name');
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData('name', $name);
    }

    /**
     * Get Is Active
     *
     * @return bool
     */
    public function getIsActive()
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set Is Active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData('is_active', $isActive ? 1 : 0);
    }

    /**
     * Get Sort Order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return (int) $this->getData('sort_order');
    }

    /**
     * Set Sort Order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData('sort_order', $sortOrder);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }
}

