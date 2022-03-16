<?php

namespace Veriteworks\Paypal\Controller\Paypal;

use \Magento\Framework\App\Action\Action;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Magento\Sales\Model\ResourceModel\Order\Payment as OrderPaymentResource;
use \Magento\Sales\Model\Order;

/**
 * Class Receive
 * @package Veriteworks\Veritrans\Controller\Mpi
 */
class SendSecure extends Action
{
    const SHOW_ORDER_URL = 'v2/checkout/orders';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;
    /**
     * @var OrderPaymentResource
     */
    private $orderPaymentResource;

    protected $client;

    protected $transferFactory;

    /**
     * Receive constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderPaymentResource $orderPaymentResource
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderPaymentResource $orderPaymentResource,
        ClientInterface $client,
        TransferFactoryInterface $transferFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->session = $checkoutSession;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderRepository = $orderRepository;
        $this->coreRegistry = $coreRegistry;
        $this->orderSender = $orderSender;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->orderPaymentResource = $orderPaymentResource;
        $this->client = $client;
        $this->transferFactory = $transferFactory;
    }
    /**
     * redirect action
     */
    public function execute()
    {
        $order = $this->orderRepository->get($this->session->getLastOrderId());
        $payment = $order->getPayment();
        $transId = $payment->getCcTransId();
        /** @var \Magento\Payment\Model\Method\Adapter $method */
        $method = $order->getPayment()->getMethodInstance();
        $params = [
            'additional_info' => [
                'method' => 'show',
                'request_id' => $transId
            ]
        ];
        $transferO = $this->transferFactory->create(
            $params
        );
        $response = $this->client->setApiPath(self::SHOW_ORDER_URL. '/'. $transId)->placeRequest($transferO);

        $this->coreRegistry->register('isSecureArea', true, true);
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(false);
        $mode = $method->getConfigData('payment_action');

        if ($mode == 'authorize_capture') {
            $invoices = $order->getInvoiceCollection();
            foreach ($invoices as $invoice) {
                $invoice->setBillingAddressId($order->getBillingAddressId());
                $invoice->setShippingAddressId($order->getShippingAddressId());
                $invoice->pay();
                $invoice->save();
            }
        }
        $this->orderPaymentResource->save($payment);
        $order->setState(Order::STATE_PROCESSING)
            ->addStatusToHistory(
                true,
                __('3D Secure authorization success.'),
                false
            );

        $this->orderRepository->save($order);
        if ($order->getCanSendNewEmailFlag()) {
            $this->orderSender->send($order);
        }
        $this->_redirect('checkout/onepage/success');
    }
}
