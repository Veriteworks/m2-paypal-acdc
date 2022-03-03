<?php
namespace Veriteworks\Paypal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Assign cc payment specific data to payment info
 */
class AssignPaypalFormData extends AbstractDataAssignObserver
{
    const CARD_NUMBER   = 'cc_number';
    const CVV           = 'cc_cid';
    const EXP_MONTH     = 'cc_exp_month';
    const EXP_YEAR      = 'cc_exp_year';
    const PAYMENT_TYPE  = 'payment_type';
    const SPLIT_COUNT   = 'split_count';
    const PAYMENT_METHOD_NONCE = 'payment_method_nonce';
    const TOKEN         = 'token';
    const INCREMENT_ID  = 'increment_id';
    const HOLDER_NAME   = 'holder_name';

    /**
     * @var array
     */
    private $additionalInformationList = [
        self::CARD_NUMBER,
        self::CVV,
        self::EXP_MONTH,
        self::EXP_YEAR,
        self::PAYMENT_TYPE,
        self::SPLIT_COUNT,
        self::TOKEN,
        self::INCREMENT_ID,
        self::HOLDER_NAME
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
