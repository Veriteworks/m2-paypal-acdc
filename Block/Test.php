<?php

namespace Veriteworks\Paypal\Block;

use Magento\Framework\View\Element\Template;
use Veriteworks\Paypal\Helper\Data as DataHelper;
use Magento\Customer\Model\Session;

class Test extends Template
{
    protected $dataHelper;

    protected $adapter;

    protected $customerSession;

    protected $retryCount = 0;

    const API_PATH = 'https://api-m.sandbox.paypal.com/v1/identity/generate-token';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        DataHelper $dataHelper,
        \Zend_Http_Client_Adapter_Curl $adapter,
        Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->adapter = $adapter;
        $this->customerSession = $customerSession;
    }

    public function getClientId()
    {
        return $this->dataHelper->getClientId();
    }

    public function getAccessToken()
    {
        return $this->dataHelper->getAccessToken();
    }

    public function getClientToken()
    {
        $config = [
            'adapter'      => $this->adapter,
            'ssltransport' => 'tls'
        ];
        $param = [
            'customer_id' => 'customer_'. $this->customerSession->getCustomer()->getId()
        ];
        $client = new \Zend_Http_Client(self::API_PATH, $config);
        $client->setHeaders('Content-type: application/json');
        $client->setHeaders("Authorization: Bearer ". $this->getAccessToken());
        $client->setHeaders("Accept-Language: en_US");
        $client->setRawData(json_encode($param), 'text/json');
        try {
            $response = $client->request('POST');
            if ($response->isError()) {
                if ($this->retryCount < 3) {
                    $this->retryCount++;
                    $this->execute($param);
                } else {
                    $returnValue['ErrCode'] = 'network error';
                }
            } else {
                $returnValue = json_decode($response->getBody(), true);
            }
        } catch (\Exception $e) {
            if ($this->retryCount < 3) {
                $this->retryCount++;
                $this->execute($param);
            } else {
                $returnValue['ErrCode'] = 'network error';
            }
        }
        return $returnValue['client_token'];
    }
}
