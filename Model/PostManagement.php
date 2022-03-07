<?php
namespace Veriteworks\Paypal\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\Order\PaymentFactory;
use Veriteworks\Paypal\Gateway\Http\Adapter\Paypal;
use Veriteworks\Paypal\Helper\Data;

class PostManagement
{
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';

    protected $paymentCollection;

    protected $paymentFactory;

    protected $resultJsonFactory;

    protected $client;

    protected $helperData;

    public function __construct(
        Collection $paymentCollection,
        PaymentFactory $paymentFactory,
        JsonFactory $resultJsonFactory,
        Paypal $client,
        Data $helperData
    ) {
        $this->paymentCollection = $paymentCollection;
        $this->paymentFactory = $paymentFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
        $this->helperData = $helperData;
    }

    /**
     * redirect action
     */
    public function getTransId($param)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($this->helperData->_getConfig('can_void'));
        $orderId = $param['orderId'];
        $transId = $this->paymentCollection->getItemById($orderId)->getCcTransId();
        return $transId;
    }

    public function capture($param)
    {
        $transactionId = $param["transaction_id"];
        $apiPath = 'v2/checkout/orders/' . $transactionId . '/capture';
        $params = [
            'additional_info' => [
                self::REQUEST_ID => $transactionId,
                self::METHOD => 'capture'
            ]
        ];
        $client = $this->client
            ->setApiPath($apiPath);
        $response = $client->execute($params);
        $captureId = $response['id'];
        $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
        $payment->setAdditionalInformation('capture_id', $captureId);
        $payment->save();

    }

    public function authorize($param)
    {
        $transactionId = $param["transaction_id"];
        $apiPath = 'v2/checkout/orders/' . $transactionId . '/authorize';
        $params = [
            'additional_info' => [
                self::REQUEST_ID => $transactionId,
                self::METHOD => 'authorize'
            ]
        ];
        $client = $this->client
            ->setApiPath($apiPath);
        $response = $client->execute($params);
        $authId = $response['purchase_units'][0]['payments']['authorizations'][0]['id'];
        $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
        $payment->setAdditionalInformation('auth_id', $authId);
        $payment->save();
        return $response;
    }
}
