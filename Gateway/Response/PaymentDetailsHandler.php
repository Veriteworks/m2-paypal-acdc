<?php
namespace Veriteworks\Paypal\Gateway\Response;

use Magento\Sales\Model\Order;
use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class PaymentDetailsHandler implements HandlerInterface
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
        /** @var array $transaction */
        $transaction = $response;
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        $payment->setCcTransId($transaction['id']);
        $payment->setLastTransId($transaction['id']);
        $txnData = [];
        $payment->unsAdditionalInformation('payment_method_nonce');
        foreach ($transaction as $key => $item) {
            if (!isset($transaction)) {
                continue;
            }
            if (in_array($key, $this->replacedKey)) {
                if (is_array($item) && count($item) > 0) {
                    $txnData['trAdUrl'] = $item[0]['url'];
                }
            } else {
                $txnData[$key] = $item;
            }
            $payment->setAdditionalInformation($key, $item);
        }
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $txnData
        );
    }
}
