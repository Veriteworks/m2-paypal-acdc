<?php
namespace Veriteworks\Paypal\Gateway\Helper;

use Magento\Quote\Model\Quote;
use Magento\Payment\Gateway\Helper;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class SubjectReader
 */
class SubjectReader
{
    /**
     * Reads response object from subject
     *
     * @param array $subject
     * @return object
     */
    public function readResponseObject(array $subject)
    {
        $response = Helper\SubjectReader::readResponse($subject);
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($response);
        if (!isset($response)) {
            throw new \InvalidArgumentException('Response object does not exist');
        }
        return $response;
    }
    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }
    /**
     * Reads transaction from subject
     *
     * @param array $subject
     * @return array
     */
    public function readResult(array $subject)
    {
        if (!isset($subject['result']) || !is_object($subject['result'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }
        return $subject['result'];
    }
    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }
    public function readTransactionId(array $subject)
    {
        if (!isset($subject['transaction_id'])) {
            throw new \InvalidArgumentException('Transaction ID does not exist');
        }
        return $subject['transaction_id'];
    }
    /**
     * Reads customer id from subject
     *
     * @param array $subject
     * @return int
     */
    public function readCustomerId(array $subject)
    {
        if (empty($subject['customer_id'])) {
            throw new \InvalidArgumentException('The "customerId" field does not exists');
        }
        return (int) $subject['customer_id'];
    }
    /**
     * Reads public hash from subject
     *
     * @param array $subject
     * @return string
     */
    public function readPublicHash(array $subject)
    {
        if (empty($subject[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new \InvalidArgumentException('The "public_hash" field does not exists');
        }
        return $subject[PaymentTokenInterface::PUBLIC_HASH];
    }
}
