<?php
namespace Veriteworks\Paypal\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\Order\PaymentFactory;
use Veriteworks\Paypal\Gateway\Http\Adapter\Paypal;

class PostManagement
{
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';

    protected $paymentCollection;

    protected $paymentFactory;

    protected $resultJsonFactory;

    protected $client;

    public function __construct(
        Collection $paymentCollection,
        PaymentFactory $paymentFactory,
        JsonFactory $resultJsonFactory,
        Paypal $client
    ) {
        $this->paymentCollection = $paymentCollection;
        $this->paymentFactory = $paymentFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
    }

    /**
     * redirect action
     */
    public function getTransId($param)
    {
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
