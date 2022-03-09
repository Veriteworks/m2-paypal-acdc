<?php
namespace Veriteworks\Paypal\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\Order\PaymentFactory;
use Veriteworks\Paypal\Gateway\Http\Adapter\Paypal;
use Veriteworks\Paypal\Helper\Data;
use Veriteworks\Paypal\Gateway\Validator\GeneralResponseValidator;

class PostManagement
{
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';

    protected $paymentCollection;

    protected $paymentFactory;

    protected $resultJsonFactory;

    protected $client;

    protected $helperData;

    protected $validator;

    public function __construct(
        Collection $paymentCollection,
        PaymentFactory $paymentFactory,
        JsonFactory $resultJsonFactory,
        Paypal $client,
        Data $helperData,
        GeneralResponseValidator $validator
    ) {
        $this->paymentCollection = $paymentCollection;
        $this->paymentFactory = $paymentFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
        $this->helperData = $helperData;
        $this->validator = $validator;
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
        $result = $this->validator->validate(['response' => $response]);
        if (!$result->isValid()) {
            //do something
        } else {
            $captureId = $response['id'];
            $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
            $payment->setAdditionalInformation('capture_id', $captureId);
            $payment->save();
        }
        return $response;
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
        $result = $this->validator->validate(['response' => $response]);
        if (!$result->isValid()) {
            //do something
        } else {
            $authId = $response['purchase_units'][0]['payments']['authorizations'][0]['id'];
            $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
            $payment->setAdditionalInformation('auth_id', $authId);
            $payment->save();
        }
        return $response;
    }
}
