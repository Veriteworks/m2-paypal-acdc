<?php

namespace Veriteworks\Paypal\Controller\Paypal;

use \Magento\Framework\App\Action\Action;

class Send extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * AbstractRemise constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->session = $checkoutSession;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->coreRegistry = $coreRegistry;
        $this->logger = $logger;
    }
    /**
     * redirect action
     */
    public function execute()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($this->session->getLastOrderId());
        $this->logger->debug('LastOrderId ' .$this->session->getLastOrderId());
        $method = $order->getPayment()->getMethod();
        /** @var \Magento\Payment\Model\Method\Adapter $method */
        $method = $order->getPayment()->getMethodInstance();
        $this->coreRegistry->register('isSecureArea', true, true);
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
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('checkout/onepage/success');
    }
}
