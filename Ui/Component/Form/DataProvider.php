<?php
/**
 * Zacatrus Events Form Data Provider
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zacatrus\Events\Ui\Component\Form;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Zacatrus\Events\Model\StoreFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param StoreFactory $storeFactory
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        StoreFactory $storeFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $storeFactory->create()->getCollection();
        $this->dataPersistor = $dataPersistor;
        $this->storeFactory = $storeFactory;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        
        $id = $this->request->getParam($this->getRequestFieldName());
        if ($id) {
            $store = $this->storeFactory->create()->load($id);
            if ($store->getId()) {
                $this->loadedData[$store->getId()] = $store->getData();
            }
        }

        $data = $this->dataPersistor->get('zacatrus_events_store');
        if (!empty($data)) {
            $store = $this->collection->getNewEmptyItem();
            $store->setData($data);
            $this->loadedData[$store->getId()] = $store->getData();
            $this->dataPersistor->clear('zacatrus_events_store');
        }

        return $this->loadedData;
    }
}

