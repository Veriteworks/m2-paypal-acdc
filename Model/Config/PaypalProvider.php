<?php
namespace Veriteworks\Paypal\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Veriteworks\Paypal\Helper\Data as DataHelper;
use Magento\Payment\Model\CcConfig;

class PaypalProvider implements ConfigProviderInterface
{
    const CODE = 'veriteworks_paypal';
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $ccConfig;
    /**
     * CvsProvider constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */

    protected $dataHelper;

    public function __construct(
        PaymentHelper $paymentHelper,
        DataHelper $dataHelper,
        CcConfig $ccConfig
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->dataHelper = $dataHelper;
        $this->ccConfig = $ccConfig;
    }
    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $storeId = $this->method->getStore();
        $config = [];
            $config = array_merge_recursive($config, [
                'payment' => [
                    self::CODE => [
                        'client_id' => $this->dataHelper->getClientId($storeId),
                        'password' => $this->dataHelper->getPassword($storeId),
                        'access_token' => $this->dataHelper->getAccessToken($storeId),
                        'payment_action' => $this->dataHelper->getPaymentAction($storeId),
                        'availableTypes' => [self::CODE => $this->ccConfig->getCcAvailableTypes()],
                        'months' => [self::CODE => $this->ccConfig->getCcMonths()],
                        'years' => [self::CODE => $this->ccConfig->getCcYears()],
                        'hasVerification' => $this->ccConfig->hasVerification(),
                    ]
                ]
            ]);
        return $config;
    }
}
