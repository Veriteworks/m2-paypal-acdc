<?php
namespace Veriteworks\Paypal\Gateway\Request\Paypal;

use Veriteworks\Paypal\Gateway\Config\Paypal;
use Veriteworks\Paypal\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Veriteworks\Paypal\Helper\Data as DataHelper;
use Magento\Directory\Model\RegionFactory;

class AuthBuilder implements BuilderInterface
{
    use Formatter;

    private $config;

    private $subjectReader;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    protected $regionFactory;

    protected $dataHelper;

    /**
     * Constructor
     *
     * @param Paypal $config
     * @param SubjectReader $subjectReader
     * @param \Magento\Framework\UrlInterface
     */
    public function __construct(
        Paypal $config,
        SubjectReader $subjectReader,
        DataHelper $dataHelper,
        RegionFactory $regionFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->dataHelper = $dataHelper;
        $this->regionFactory = $regionFactory;
        $this->url = $url;
    }
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order     = $paymentDO->getOrder();
        $payment     = $paymentDO->getPayment();
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $storeId   = $order->getStoreId();
        $amount    = $this->subjectReader->readAmount($buildSubject);
        $paymentAction = $this->getPaymentAction($this->dataHelper->getPaymentAction());
        $payment->setAdditionalInformation('method', $paymentAction);
        $siteName = $this->config->getValue('name', $storeId);
        $result = [
            "param" => [
                "intent" => $paymentAction,
                "application_context" => [
                    "shipping_preference" => "SET_PROVIDED_ADDRESS",
                    "brand_name" => $siteName
                ],
                "payer" => [
                    "name" => [
                        "given_name" => $billing->getFirstname(),
                        "surname" => $billing->getLastname()
                    ],
                    "email_address" => $billing->getEmail(),
                    "address" => [
                        "address_line_1" => $billing->getStreetLine1(),
                        "address_line_2" => $billing->getStreetLine2(),
                        "admin_area_1" => $billing->getRegionCode(),
                        "admin_area_2" => $billing->getCity(),
                        "postal_code" => $billing->getPostcode(),
                        "country_code" => $billing->getCountryId()
                    ]
                ],
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => $order->getCurrencyCode(),
                            "value" => $amount
                        ],
                        "shipping" => [
                            "name" => [
                                "full_name" => $shipping->getFirstname(). ' '. $shipping->getLastname()
                            ],
                            "address" => [
                                "address_line_1" => $shipping->getStreetLine1(),
                                "address_line_2" => $shipping->getStreetLine2(),
                                "admin_area_2" => $shipping->getCity(),
                                "admin_area_1" => $shipping->getRegionCode(),
                                "postal_code" => $shipping->getPostcode(),
                                "country_code" => $shipping->getCountryId()
                            ]
                        ],
                        "invoice_id" => $order->getId(),
                        "custom_id" => $order->getId()
                    ]
                ]
            ],
            "additional_info" => [
                "method" => "create"
            ]
        ];
        return $result;
    }

    private function getPaymentAction($action)
    {
        if ($action === 'authorize') {
            return 'AUTHORIZE';
        } else {
            return 'CAPTURE';
        }
    }
}
