<?php

namespace Veriteworks\Paypal\Model;

use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Veriteworks\Paypal\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;

class GetTransId
{
    protected $paymentRepository;

    protected $logger;

    public function __construct(
        OrderPaymentRepositoryInterface $paymentRepository,
        Logger $logger
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($param)
    {
        $errmsg = ['errmsg' => ['err' => true, 'custom' => 'order id doesn\'t exist.']];
        if (array_key_exists('orderId', $param)) {
            $orderId = $param['orderId'];
        } else {
            return $errmsg;
        }
        try {
            return $this->paymentRepository->get($orderId)->getCcTransId();
        } catch (NoSuchEntityException $e) {
            $this->logger->debug($e->getMessage());
            return $errmsg;
        } catch (\Exception $e) {
            return ['err' => ['custom' => 'error happened.']];
        }
    }
}
