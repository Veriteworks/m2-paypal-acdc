<?php
namespace Veriteworks\Paypal\Gateway\Http\Adapter;

use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Exception\LocalizedException;
use Veriteworks\Paypal\Helper\Data as DataHelper;

class Paypal implements AdapterInterface
{
    /**
     * @var int
     *
     * Retry Counter
     */
    protected $retryCount = 0;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Veriteworks\Paypal\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Zend_Http_Client_Adapter_Curl
     */
    protected $adapter;
    /**
     * @var string
     */
    protected $apiPath;

    protected $dataHelper;

    private $storeId;

    /**
     * GATEWAY URL
     */
    const GATEWAY_URL = 'https://api-m.paypal.com/';

    const GATEWAY_URL_TEST = 'https://api-m.sandbox.paypal.com/';

    /**
     * Abstact constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Zend_Http_Client_Adapter_Curl $adapter
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Veriteworks\Paypal\Logger\Logger $logger,
        \Zend_Http_Client_Adapter_Curl $adapter,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->adapter = $adapter;
        $this->dataHelper = $dataHelper;
    }
    /**
     * @param $path
     */
    public function setApiPath($path)
    {
        if ($this->getConfig('is_test')) {
            $this->apiPath = self::GATEWAY_URL_TEST . $path;
        } else {
            $this->apiPath = self::GATEWAY_URL . $path;
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }
    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function execute(array $param)
    {
        $returnValue = [];
        $config = [
            'adapter'      => $this->adapter,
            'ssltransport' => 'tls'
        ];
        $additionalInfo = $param["additional_info"];
        $client = new \Zend_Http_Client($this->apiPath, $config);
        $client->setHeaders('Content-type: application/json');
        $client->setHeaders("Authorization: Bearer ". $this->dataHelper->getAccessToken());
        $client->setHeaders("PayPal-Partner-Attribution-Id: BN_CODE");
        if ($additionalInfo["method"] === 'create') {
            $client->setRawData(json_encode($param["param"]), 'text/json');
        } else {
            $client->setHeaders("PayPal-Mock-Response: {\"mock_application_codes\" : \"INSTRUMENT_DECLINED\"}");
            $client->setHeaders("PayPal-Request-Id: request-" . $additionalInfo["request_id"]);
        }
        try {
            $response = $client->request('POST');
            if ($response->isError()) {
                if ($this->retryCount < 3) {
                    $this->retryCount++;
                    $returnValue['code'] = $response->getStatus();
                    $returnValue['message'] = $response->getMessage();
                    $this->execute($param);
                } else {
                    $this->log(var_export($this->apiPath, true));
                    $this->log(var_export($response, true));
                    $returnValue['code'] = $response->getStatus();
                    $returnValue['message'] = $response->getMessage();
                }
            } else {
                $this->log(var_export($this->apiPath, true));
                $this->log(var_export($response, true));
                $returnValue = json_decode($response->getBody(), true);
                $returnValue['code'] = $response->getStatus();
                $returnValue['message'] = $response->getMessage();
            }
        } catch (\Exception $e) {
            if ($this->retryCount < 3) {
                $this->retryCount++;
                $this->execute($param);
            } else {
                $this->log(var_export($this->apiPath, true));
                $this->log(var_export($response, true));
                $returnValue['code'] = $response->getStatus();
                $returnValue['message'] = $response->getMessage();
            }
        }
        return $returnValue;
    }
    /**
     * Record log
     *
     * @param string $str Log string
     */
    protected function log($str)
    {
        $this->logger->info($str);
    }
    /**
     * @param $key
     * @return mixed
     */
    protected function getConfig($key)
    {
        $key = 'payment/veriteworks_paypal/' . $key;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORES, $this->storeId);
    }
    /**
     * @param array $body
     * @return string
     */
    protected function generateHash(array $body)
    {
        $json = json_encode($body['params']);
        $merchantId = $this->getConfig('merchant_id');
        $secretKey = $this->getConfig('merchant_password');
        $hash = hash('SHA256', $merchantId . $json . $secretKey, false);
        return $hash;
    }
}
