<?php

namespace Veriteworks\Paypal\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class CaptureCommand
 * @SuppressWarnings(PHPMD)
 */
class CaptureCommandStrategy implements CommandInterface
{
    const SALE = 'sale';
    const CAPTURE = 'settlement';
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    private $key;

    /**
     * CaptureCommandStrategy constructor.
     * @param CommandPoolInterface $commandPool
     * @param ScopeConfigInterface $scopeConfig
     * @param string $key
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        ScopeConfigInterface $scopeConfig,
        $key = 'payment/veriteworks_paypal/'
    ) {
        $this->commandPool = $commandPool;
        $this->scopeConfig = $scopeConfig;
        $this->key = $key;
    }
    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        $paymentDo = $commandSubject['payment'];
        $order = $paymentDo->getOrder();
        $storeId = $order->getStoreId();
        $mode = $this->getConfig('payment_action', $storeId);
        $command = $this->getCommand($mode);
        $this->commandPool->get($command)->execute($commandSubject);
    }
    /**
     * Get execution command name
     * @param string $mode
     * @return string
     */
    private function getCommand($mode)
    {
        if ($mode == 'authorize_capture') {
            return self::SALE;
        }
        if ($mode == 'authorize') {
            return self::CAPTURE;
        }
    }
    /**
     * @param $key
     * @param $storeId int | null
     * @return mixed
     */
    private function getConfig($key, $storeId = null)
    {
        $key = $this->key . $key;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES, $storeId);
    }
}
