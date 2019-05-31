<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Lamoda\QueueBundle\Consumer;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;
use Lamoda\QueueBundle\Exception\AttemptsReachedException;
use Lamoda\QueueBundle\Exception\RuntimeException;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumerTest extends Unit
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    /**
     * @param QueueEntityInterface $queueEntity
     * @param int                  $expected
     *
     * @throws Exception
     *
     * @dataProvider dataDoExecute
     */
    public function testDoExecute(QueueEntityInterface $queueEntity, int $expected): void
    {
        $mockQueueService = $this->getMockQueueService(['save']);
        $mockQueueService->expects($this->once())
            ->method('save')
            ->with($queueEntity);

        $mockConsumer = $this->getMockConsumer(['executeQueue']);
        $mockConsumer->expects($this->once())
            ->method('executeQueue')
            ->with($queueEntity);

        Reflection::setProtectedProperty($mockConsumer, 'queueService', $mockQueueService);
        Reflection::setProtectedProperty($mockConsumer, 'logger', $this->getMockLogger());

        $this->assertEquals($expected, Reflection::callProtectedMethod($mockConsumer, 'doExecute', $queueEntity));
        $this->assertEquals(QueueEntityMappedSuperclass::STATUS_DONE, $queueEntity->getStatus());
    }

    public function dataDoExecute(): array
    {
        return [
            [
                $this->getQueue(),
                Consumer::MSG_ACK,
            ],
        ];
    }

    /**
     * @param QueueEntityInterface $queueEntity
     * @param Exception            $exception
     * @param int                  $expectedStatus
     * @param int                  $expected
     *
     * @throws Exception
     *
     * @dataProvider dataDoExecuteException
     */
    public function testDoExecuteException(
        QueueEntityInterface $queueEntity,
        Exception $exception,
        int $expectedStatus,
        int $expected
    ): void {
        $mockQueueService = $this->getMockQueueService(['save']);
        $mockQueueService->expects($this->once())
            ->method('save')
            ->with($queueEntity);

        $mockConsumer = $this->getMockConsumer(['executeQueue']);
        $mockConsumer->expects($this->once())
            ->method('executeQueue')
            ->with($queueEntity)
            ->will($this->throwException($exception));
        $mockDelayService = $this->getMockDelayService(['delayQueue']);
        $mockDelayService->method('delayQueue')
            ->willReturnCallback(function (QueueEntityInterface $queue) {return $queue->setWaiting(new DateTime()); });

        Reflection::setProtectedProperty($mockConsumer, 'queueService', $mockQueueService);
        Reflection::setProtectedProperty($mockConsumer, 'logger', $this->getMockLogger());
        Reflection::setProtectedProperty($mockConsumer, 'delayService', $mockDelayService);

        $this->assertEquals($expected, Reflection::callProtectedMethod($mockConsumer, 'doExecute', $queueEntity));
        $this->assertEquals($expectedStatus, $queueEntity->getStatus());
    }

    public function dataDoExecuteException(): array
    {
        return [
            [
                $this->getQueue(),
                new RuntimeException(),
                QueueEntityMappedSuperclass::STATUS_WAITING,
                Consumer::MSG_ACK,
            ],
            [
                $this->getQueue(),
                new Exception(),
                QueueEntityMappedSuperclass::STATUS_ERROR,
                Consumer::MSG_ACK,
            ],
        ];
    }

    /**
     * @param QueueEntityInterface $queueEntity
     * @param AMQPMessage          $message
     * @param int                  $result
     * @param int                  $id
     *
     * @throws Exception
     *
     * @dataProvider dataExecute
     */
    public function testExecute(QueueEntityInterface $queueEntity, AMQPMessage $message, $result, $id): void
    {
        $mockQueueService = $this->getMockQueueService(['getToProcess']);
        $mockQueueService->expects($this->once())
            ->method('getToProcess')
            ->with($id)
            ->willReturn($queueEntity);

        $mockConsumer = $this->getMockConsumer(['doExecute']);
        $mockConsumer->expects($this->once())
            ->method('doExecute')
            ->with($queueEntity)
            ->willReturn($result);

        $mockEntityManager = $this->getMockEntityManager(['clear']);
        $mockEntityManager->expects($this->once())
            ->method('clear');

        Reflection::setProtectedProperties($mockConsumer, [
            'queueService' => $mockQueueService,
            'entityManager' => $mockEntityManager,
        ]);

        $this->assertEquals($result, $mockConsumer->execute($message));
    }

    public function dataExecute(): array
    {
        $id = 1;

        return [
            [
                $this->getQueue(),
                $this->getMessage(json_encode(['id' => $id])),
                Consumer::MSG_ACK,
                $id,
            ],
        ];
    }

    /**
     * @param AMQPMessage $message
     *
     * @throws Exception
     *
     * @dataProvider dataExecuteBrokenMessage()
     */
    public function testExecuteBrokenMessage(AMQPMessage $message, string $expectedErrorMessage): void
    {
        $mockLogger = $this->getMockLogger(['alert']);
        $mockLogger->expects($this->once())
            ->method('alert')
            ->with($expectedErrorMessage);

        $mockConsumer = $this->getMockConsumer(['doExecute']);

        Reflection::setProtectedProperties($mockConsumer, [
            'logger' => $mockLogger,
        ]);

        $this->assertEquals($mockConsumer::MSG_REJECT, $mockConsumer->execute($message));
    }

    public function dataExecuteBrokenMessage(): array
    {
        return [
            [
                $this->getMessage(json_encode(['name' => 'name'])),
                'Data was damaged. Remove message from queue',
            ],
            [
                $this->getMessage('abrakadabra'),
                'json_decode error: Syntax error',
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function testExecuteException(): void
    {
        $mockQueueService = $this->getMockQueueService(['getToProcess']);
        $mockQueueService->expects($this->once())
            ->method('getToProcess')
            ->will($this->throwException(new UnexpectedValueException()));

        $mockEntityManager = $this->getMockEntityManager(['clear']);
        $mockEntityManager->expects($this->once())
            ->method('clear');

        $consumer = $this->getMockConsumer(['executeQueue']);
        Reflection::setProtectedProperties($consumer, [
            'queueService' => $mockQueueService,
            'entityManager' => $mockEntityManager,
            'logger' => $this->getMockLogger(),
        ]);

        $this->assertEquals($consumer::MSG_REJECT, $consumer->execute($this->getMessage()));
    }

    /**
     * @throws Exception
     */
    public function testExecuteAttemptsReachedException(): void
    {
        $mockQueueService = $this->getMockQueueService(['getToProcess']);
        $mockQueueService->expects($this->once())
            ->method('getToProcess')
            ->will($this->throwException(new AttemptsReachedException()));

        $mockEntityManager = $this->getMockEntityManager(['clear']);
        $mockEntityManager->expects($this->once())
            ->method('clear');

        $consumer = $this->getMockConsumer(['executeQueue']);
        Reflection::setProtectedProperties($consumer, [
            'queueService' => $mockQueueService,
            'entityManager' => $mockEntityManager,
            'logger' => $this->getMockLogger(),
        ]);

        $this->assertEquals($consumer::MSG_REJECT, $consumer->execute($this->getMessage()));
    }

    /**
     * @return QueueEntityInterface
     *
     * @throws Exception
     */
    protected function getQueue(): QueueEntityInterface
    {
        $queue = new QueueEntity('queue-name', 'exchange', 'ClassJob', ['id' => 1]);

        Reflection::setProtectedProperty($queue, 'id', 1);

        return $queue;
    }

    protected function getMessage(string $data = '{"id": 1}'): AMQPMessage
    {
        /** @var \PhpAmqpLib\Channel\AMQPChannel | \PHPUnit_Framework_MockObject_MockObject $channelMock */
        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'basic_ack',
                'basic_reject',
                '__destruct',
            ])
            ->getMock();

        $message = new AMQPMessage();
        $message->delivery_info = [
            'channel' => $channelMock,
            'delivery_tag' => uniqid(),
        ];
        $message->setBody($data);

        return $message;
    }
}
