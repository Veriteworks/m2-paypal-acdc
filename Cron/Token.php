<?php

namespace Veriteworks\Paypal\Cron;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Veriteworks\Paypal\Logger\Logger;

class Token
{

    const API_URL = 'v1/oauth2/token';

    const URL_PREFIX = 'https://api-m.paypal.com/';

    const URL_TEST_PREFIX = 'https://api-m.sandbox.paypal.com/';

    protected $retryCount = 0;

    protected $configWriter;

    protected $logger;

    protected $scopeConfig;

    protected $apiPath;

    public function __construct(
        WriterInterface $configWriter,
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $returnValue = [];

        $this->setApiPath(self::API_URL);

        $client = new \Zend_Http_Client($this->apiPath);
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'en_US'
        ];
        $client->setHeaders($headers);

        $user = $this->getConfig('merchant_id');
        $password = $this->getConfig('merchant_password');
        $client->setAuth($user, $password);

        $client->setRawData("grant_type=client_credentials");
        try {
            $response = $client->request('POST');
            if ($response->isError()) {
                if ($this->retryCount < 3) {
                    $this->retryCount++;
                } else {
                    $this->log(var_export($this->apiPath, true));
                    $this->log(var_export($response, true));
                    $returnValue['ErrCode'] = 'network error';
                }
            } else {
                $this->log(var_export($this->apiPath, true));
                $this->log(var_export($response, true));
                $returnValue = json_decode($response->getBody(), true);
            }
        } catch (\Exception $e) {
            if ($this->retryCount < 3) {
                $this->retryCount++;
            } else {
                $this->log(var_export($this->apiPath, true));
                $this->log(var_export($response, true));
                $returnValue['ErrCode'] = 'network error';
            }
        }

        $this->configWriter->save('payment/veriteworks_paypal/access_token', $returnValue['access_token']);
    }

    public function setApiPath($path)
    {
        if ($this->getConfig('is_test')) {
            $this->apiPath = self::URL_TEST_PREFIX . $path;
        } else {
            $this->apiPath = self::URL_PREFIX . $path;
        }
        return $this;
    }

    protected function getConfig($key)
    {
        $key = 'payment/veriteworks_paypal/' . $key;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES);
    }

    protected function log($str)
    {
        $this->logger->info($str);
    }
}
