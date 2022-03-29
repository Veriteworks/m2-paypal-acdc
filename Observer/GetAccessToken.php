<?php
namespace Veriteworks\Paypal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Veriteworks\Paypal\Helper\Data;
use Veriteworks\Paypal\Cron\Token;

class GetAccessToken implements ObserverInterface
{
    protected $configData;

    protected $token;

    public function __construct(
        Data $configData,
        Token $token
    ) {
        $this->configData = $configData;
        $this->token = $token;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $section = $observer->getEvent()->getData('configData')['section'];
        if ($section === 'payment' && !$this->existAccessToken()) {
            $this->token->execute();
        }
    }

    private function existAccessToken()
    {
        return $this->configData->getAccessToken() !== null;
    }
}
