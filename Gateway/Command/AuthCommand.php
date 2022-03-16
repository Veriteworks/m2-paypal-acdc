<?php

namespace Veriteworks\Paypal\Gateway\Command;

use Veriteworks\Paypal\Gateway\Command\logExceptions;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;

/**
 * Class AuthCommand
 * @SuppressWarnings(PHPMD)
 */
class AuthCommand implements CommandInterface
{
    use logExceptions;
    /**
     * @var BuilderInterface
     */
    private $requestBuilder;
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var HandlerInterface
     */
    private $handler;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;
    /**
     * AuthCommand constructor.
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Magento\Payment\Gateway\Http\TransferFactoryInterface $transferFactory
     * @param \Magento\Payment\Gateway\Http\ClientInterface $client
     * @param \Psr\Log\LoggerInterface $logger
     * @param CheckoutHelper $checkoutHelper
     * @param \Magento\Payment\Gateway\Response\HandlerInterface|null $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface|null $validator
     */
    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        LoggerInterface $logger,
        CheckoutHelper $checkoutHelper,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->checkoutHelper = $checkoutHelper;
    }
    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );
        $apiPath = 'v2/checkout/orders';
        $paymentDo = $commandSubject['payment'];
        $order = $paymentDo->getOrder();
        $storeId = $order->getStoreId();
        $response = $this->client
            ->setStoreId($storeId)
            ->setApiPath($apiPath)
            ->placeRequest($transferO);
        if ($this->validator !== null) {
            $result = $this->validator->validate(
                array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $messages = $result->getFailsDescription();
                $this->logExceptions($result->getFailsDescription());
                $this->checkoutHelper->sendPaymentFailedEmail(
                    $this->checkoutHelper->getQuote(),
                    $messages[0]
                );
                throw new CommandException(
                    __('Transaction has been declined. Please try again later.')
                );
            }
        }
        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }
    }
}
