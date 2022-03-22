<?php
namespace Veriteworks\Paypal\Gateway\Response;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AuthorizeHandler implements HandlerInterface
{
    /**
     * List of repleaced keys
     * @var array
     */
    protected $replacedKey = [
        'optionResults'
    ];
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    private $scopeConfig;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        if ($this->getConfig('use_3dsecure')) {
            $payment->setIsTransactionPending(true);
        }
    }

    private function getConfig($key, $storeId = null)
    {
        $key = 'payment/veriteworks_paypal/' . $key;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES, $storeId);
    }
}
