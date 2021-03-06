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

    protected $exceptionNoSuchEntity;

    public function setUp()
    {
        $this->payment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(OrderPaymentRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->exceptionNoSuchEntity = $this->createMock(NoSuchEntityException::class);
        $this->exception = $this->createMock(\Exception::class);
        $this->getTransId = new GetTransId($this->paymentRepository, $this->logger);

        $this->paymentRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->payment);
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

        $this->assertEquals($errmsg, $this->getTransId->execute([]));
        $this->assertEquals('TRANSID', $this->getTransId->execute(['orderId' => 'ORDERID']));
    }

    public function testExecuteExceptionNoSuchEntity()
    {
        $errmsg = ['errmsg' => ['err' => true, 'custom' => 'Settlement doesn\'t exist.']];

        $this->payment->expects($this->once())
            ->method('getCcTransId')
            ->willThrowException($this->exceptionNoSuchEntity);

        $this->assertEquals($errmsg, $this->getTransId->execute(['orderId' => 'ORDERID']));
    }

    public function testExecuteException()
    {
        $errmsg = ['errmsg' =>['err' => true, 'custom' => 'error happened.']];

        $this->payment->expects($this->once())
            ->method('getCcTransId')
            ->willThrowException($this->exception);

        $this->assertEquals($errmsg, $this->getTransId->execute(['orderId' => 'ORDERID']));
    }
}
