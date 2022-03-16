<?php
namespace Veriteworks\Paypal\Gateway\Config;

use \Magento\Payment\Gateway\Config\Config as BaseConfig;

/**
 * Class Config
 */
class PaypalOnboard extends BaseConfig
{
    const CODE = 'veriteworks_paypal_onboard';
    const KEY_ACTIVE = 'active';
    /**
     * Get Payment configuration status
     * @param $storeId int | null
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }
}
