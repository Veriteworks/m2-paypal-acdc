<?php
namespace Veriteworks\Paypal\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Veriteworks\Paypal\Logger\Logger;
use Veriteworks\Paypal\Model\GetTransId;

class GetTransIdTest extends \PHPUnit\Framework\TestCase
{
    const REQUEST_ID = 'request_id';
    const METHOD = 'method';

    protected $payment;

    protected $paymentRepository;

    protected $getTransId;

    protected $logger;

    protected $exception;

    public function setUp()
    {
        $this->payment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(OrderPaymentRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->exception = $this->createMock(NoSuchEntityException::class);
        $this->getTransId = new GetTransId($this->paymentRepository, $this->logger);
    }

    /**
     * redirect action
     */
    public function testExecute()
    {
        $errmsg = ['errmsg' => ['err' => true, 'custom' => 'order id doesn\'t exist.']];

        $this->payment->expects($this->once())
            ->method('getCcTransId')
            ->willReturn('TRANSID');

        $this->paymentRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->payment);

        $this->assertEquals($errmsg, $this->getTransId->execute([]));
        $this->assertEquals('TRANSID', $this->getTransId->execute(['orderId' => 'ORDERID']));
    }

    public function testExecuteException()
    {
        $errmsg = ['errmsg' => ['err' => true, 'custom' => 'Settlement doesn\'t exist.']];

        $this->payment->expects($this->once())
            ->method('getCcTransId')
            ->willThrowException($this->exception);

        $this->paymentRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->payment);

        $this->assertEquals($errmsg, $this->getTransId->execute(['orderId' => 'ORDERID']));
    }
}
