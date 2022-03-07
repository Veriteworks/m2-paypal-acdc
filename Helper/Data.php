<?php
namespace Veriteworks\Paypal\Helper;

use \Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param $key
     * @return mixed
     */
    public function _getConfig($key, $storeId = null)
    {
        $key = 'payment/veriteworks_paypal/' . $key;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES, $storeId);
    }

    public function getClientId($storeId = null)
    {
        return $this->_getConfig('merchant_id', $storeId);
    }

    public function getPassword($storeId = null)
    {
        return $this->_getConfig('merchant_password', $storeId);
    }

    public function getAccessToken($storeId = null)
    {
        return $this->_getConfig('access_token', $storeId);
    }

    public function getPaymentAction($storeId = null)
    {
        return $this->_getConfig('payment_action', $storeId);
    }
}
