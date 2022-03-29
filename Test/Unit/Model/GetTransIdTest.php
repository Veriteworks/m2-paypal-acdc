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
        $this->payment->expects($this->any())
            ->method('getCcTransId')
            ->willReturn('TRANSID');

        $this->paymentRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->payment);

        $this->assertEquals(false, $this->getTransId->execute([]));
        $this->assertEquals('TRANSID', $this->getTransId->execute(['orderId' => 'ORDERID']));
    }

    public function testExecuteException()
    {
        $this->payment->expects($this->any())
            ->method('getCcTransId')
            ->willReturn('TRANSID');

        $this->paymentRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->payment);

        $this->assertEquals($this->exception, $this->getTransId->execute(['orderId' => 'ORDERID']));
    }
}
