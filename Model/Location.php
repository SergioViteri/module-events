<?php
/**
 * Zacatrus Events Location Model
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model;

use Zaca\Events\Model\ResourceModel\Location as LocationResourceModel;
use Magento\Framework\Model\AbstractModel;

class Location extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LocationResourceModel::class);
    }

    /**
     * Get Location ID
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->getData('location_id');
    }

    /**
     * Set Location ID
     *
     * @param int $locationId
     * @return $this
     */
    public function setLocationId($locationId)
    {
        return $this->setData('location_id', $locationId);
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
     * Get Address
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->getData('address');
    }

    /**
     * Set Address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        return $this->setData('address', $address);
    }

    /**
     * Get City
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->getData('city');
    }

    /**
     * Set City
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        return $this->setData('city', $city);
    }

    /**
     * Get Postal Code
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->getData('postal_code');
    }

    /**
     * Set Postal Code
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        return $this->setData('postal_code', $postalCode);
    }

    /**
     * Get Country
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->getData('country');
    }

    /**
     * Set Country
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        return $this->setData('country', $country);
    }

    /**
     * Get Latitude
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->getData('latitude');
    }

    /**
     * Set Latitude
     *
     * @param float $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        return $this->setData('latitude', $latitude);
    }

    /**
     * Get Longitude
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->getData('longitude');
    }

    /**
     * Set Longitude
     *
     * @param float $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        return $this->setData('longitude', $longitude);
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

