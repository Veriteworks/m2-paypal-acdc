<?php
namespace Veriteworks\Paypal\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;
use Magento\Sales\Model\Order\PaymentFactory;
use Veriteworks\Paypal\Helper\Data;
use Veriteworks\Paypal\Gateway\Validator\GeneralResponseValidator;
use Veriteworks\Paypal\Logger\Logger;

class PostManagement
{
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';

    protected $paymentCollection;

    protected $paymentFactory;

    protected $resultJsonFactory;

    protected $client;

    protected $transferFactory;

    protected $helperData;

    protected $validator;

    protected $logger;

    public function __construct(
        Collection $paymentCollection,
        PaymentFactory $paymentFactory,
        JsonFactory $resultJsonFactory,
        ClientInterface $client,
        TransferFactoryInterface $transferFactory,
        Data $helperData,
        GeneralResponseValidator $validator,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        Logger $logger
    ) {
        $this->paymentCollection = $paymentCollection;
        $this->paymentFactory = $paymentFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
        $this->transferFactory = $transferFactory;
        $this->helperData = $helperData;
        $this->validator = $validator;
        $this->orderRepository = $orderRepository;
        $this->session = $checkoutSession;
        $this->logger = $logger;
    }

    public function capture($param)
    {
        $payload = $param['payload'];
        $use3ds = $this->helperData->getUse3DS();
        if ($use3ds) {
            if (!array_key_exists('liabilityShift', $payload) || $payload['liabilityShift'] !== 'POSSIBLE') {
                return [$this->processError([
                    'error' => [
                        'err_intent' => '3ds_err'
                    ]
                ])];
            }
        }
        $transactionId = $payload['orderId'];
        $apiPath = 'v2/checkout/orders/' . $transactionId. '/capture';
        $params = [
            'additional_info' => [
                self::REQUEST_ID => $transactionId,
                self::METHOD => 'capture'
            ]
        ];
        $transferO = $this->transferFactory->create($params);
        $response = $this->client
            ->setApiPath($apiPath)->placeRequest($transferO);
        $result = $this->validator->validate(['response' => $response]);
        if (!$result->isValid()) {
            $response = [$this->processError([
                'error' => [
                    'err_intent' => 'capture_err',
                    'err_description' => $result->getFailsDescription()
                    ]
            ])];
        } else {
            $captureId = $response['purchase_units'][0]['payments']['captures'][0]['id'];
            $this->logger->debug(var_export($captureId, true));
            $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
            $payment->setAdditionalInformation('capture_id', $captureId);
            $payment->save();
        }
        return $response;
    }

    public function authorize($param)
    {
        $payload = $param['payload'];
        $transactionId = $payload['orderId'];
        $apiPath = 'v2/checkout/orders/' . $transactionId . '/authorize';
        $params = [
            'additional_info' => [
                self::REQUEST_ID => $transactionId,
                self::METHOD => 'authorize'
            ]
        ];
        $transferO = $this->transferFactory->create($params);
        $response = $this->client
            ->setApiPath($apiPath)->placeRequest($transferO);
        $result = $this->validator->validate(['response' => $response]);
        $use3ds = $this->helperData->getUse3DS();
        if (!$result->isValid()) {
            $response = [$this->processError([
                'error' => [
                    'err_intent' => 'authorize_err',
                    'err_description' => $result->getFailsDescription()
                ]
            ])];
        } else {
            $authId = $response['purchase_units'][0]['payments']['authorizations'][0]['id'];
            $payment = $this->paymentFactory->create()->load($transactionId, 'cc_trans_id');
            $payment->setAdditionalInformation('auth_id', $authId);
            $payment->save();
        }
        if ($use3ds) {
            if (!array_key_exists('liabilityShift', $payload) || $payload['liabilityShift'] !== 'POSSIBLE') {
                $response = [$this->processError([
                    'error' => [
                        'err_intent' => '3ds_err',
                    ]
                ])];
            }
        }
        return $response;
    }

    public function processError($param)
    {
        $order = $this->orderRepository->get($this->session->getLastOrderId());
        $order->cancel();
        $this->session->restoreQuote();
        $this->orderRepository->delete($order);
        $error = $param['error'];
        $this->logger->error(var_export($error, true));
        return $error;
    }
}
