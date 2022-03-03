<?php
namespace Veriteworks\Paypal\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class GeneralResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;
    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }
    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        /** @var array $response */
        $response = $this->subjectReader->readResponseObject($validationSubject);
        $isValid = true;
        $errorMessages = [];
//        if (array_key_exists('mstatus', $response)
//            && ($response['mstatus'] == 'success')
//        ) {
//            return $this->createResult($isValid, $errorMessages);
//        } else {
//            $resultCode = substr($response['vResultCode'], 0, 4);
//            if ($resultCode != 'NH18') {
//                $isValid = false;
//                $errorMessages[] = $resultCode . ":" . $response['merrMsg'];
//            }
//        }
        return $this->createResult($isValid, $errorMessages);
    }
}
