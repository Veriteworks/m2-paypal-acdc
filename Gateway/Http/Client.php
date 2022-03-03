<?php
namespace Veriteworks\Paypal\Gateway\Http;

use Veriteworks\Paypal\Gateway\Http\Adapter\AdapterInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class Client implements ClientInterface
{
    private $adapter;
    /**
     * @var ConverterInterface | null
     */
    private $converter;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * Constructor
     *
     * @param Logger $logger
     * @param AdapterInterface $adapter
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        AdapterInterface $adapter,
        $data = []
    ) {
        $this->logger = $logger;
        $this->adapter = $adapter;
    }
    /**
     * @param $path
     * @return $this
     */
    public function setApiPath($path)
    {
        $this->adapter->setApiPath($path);
        return $this;
    }

    /**
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return mixed
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $config = $transferObject->getClientConfig();
        $result = $this->adapter->execute($transferObject->getBody());
        return $result;
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->adapter->setStoreId($storeId);
        return $this;
    }
}
