<?php

namespace Veriteworks\Paypal\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;

/**
 * Class CurrencyValidator
 * @package Magento\Payment\Gateway\Validator
 * @api
 */
class MultiCurrencyValidator extends AbstractValidator
{
    /**
     * @var \Magento\Payment\Gateway\ConfigInterface
     */
    private $config;
    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }
    /**
     * @param array $validationSubject
     * @return ResultInterface
     * @throws NotFoundException
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $storeId = $validationSubject['storeId'];
        $currencies = $this->config->getValue('currency', $storeId);
        $allowedCurrencies = explode(',', $currencies);
        if (!in_array($validationSubject['currency'], $allowedCurrencies)) {
            $isValid =  false;
        }
        return $this->createResult($isValid);
    }
}
