<?php
namespace Veriteworks\Paypal\Gateway\Response;

use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class VoidHandler implements HandlerInterface
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
    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }
    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        $payment->setAdditionalInformation('void', 'voided');
    }
}
