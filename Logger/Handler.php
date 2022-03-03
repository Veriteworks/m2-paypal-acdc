<?php
namespace Veriteworks\Paypal\Logger;

use Monolog\Logger as Monologger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Monologger::INFO;
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/paypal.log';
}
