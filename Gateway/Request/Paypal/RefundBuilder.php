<?php

namespace Veriteworks\Paypal\Gateway\Request\Paypal;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Veriteworks\Paypal\Gateway\Config\Paypal;
use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Payment\Model\InfoInterface;
use \Magento\Framework\Exception\LocalizedException;

class RefundBuilder implements BuilderInterface
{
    use Formatter;
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';
    /**
     * @var \Veriteworks\Paypal\Gateway\Config\Paypal
     */
    private $config;
    /**
     * @var \Veriteworks\Paypal\Gateway\Helper\SubjectReader
     */
    private $subjectReader;
    /**
     * Constructor
     *
     * @param Paypal $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(Paypal $config, SubjectReader $subjectReader)
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $payment->setAdditionalInformation('method', 'REFUND');
        $transactionId = $payment->getCcTransId();
        if (!$transactionId) {
            throw new LocalizedException(__('No authorization transaction to proceed refund.'));
        }
        $result = [
            'additional_info' => [
                    self::REQUEST_ID => $transactionId,
                    self::METHOD => 'refund'
                ]
        ];
        return $result;
    }
}
