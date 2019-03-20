<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit;

use Codeception\Test\Unit;
use DateTime;
use ErrorException;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;

class PublisherTest extends Unit
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    /**
     * @param QueueEntityInterface $queue
     * @param QueueEntityInterface $queueExpected
     *
     * @throws \Exception
     *
     * @dataProvider dataRelease
     */
    public function testRelease(QueueEntityInterface $queue, QueueEntityInterface $queueExpected): void
    {
        $queueService = $this->getMockQueueService(['isTransactionActive', 'save']);
        $queueService->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn(false);
        $queueService->expects($this->once())
            ->method('save')
            ->with($queueExpected);

        $publisher = $this->getMockPublisher(['publishQueue']);
        $publisher->expects($this->once())
            ->method('publishQueue')
            ->with($queue);

        Reflection::setProtectedProperty($publisher, 'queueService', $queueService);

        $publisher->prepareQueueForPublish($queue);
        $publisher->release();

        $this->assertEquals(QueueEntityMappedSuperclass::STATUS_NEW, $queue->getStatus());
    }

    public function dataRelease(): array
    {
        $queue1 = new QueueEntity('queue', 'exchange', 'StubJob', ['value' => 1]);

        $queue2 = clone $queue1;
        $queue2->setNew();

        return [
            [$queue1, $queue2],
        ];
    }

    /**
     * @param QueueEntityInterface $queue
     *
     * @throws \Exception
     *
     * @dataProvider dataReleaseWithDelay
     */
    public function testReleaseWithDelay(QueueEntityInterface $queue): void
    {
        $queueService = $this->getMockQueueService(['isTransactionActive', 'save']);
        $queueService->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn(false);

        $queueService->expects($this->never())
            ->method('save');

        $publisher = $this->getMockPublisher(['publishQueue']);
        $publisher->expects($this->never())
            ->method('publishQueue');

        Reflection::setProtectedProperty($publisher, 'queueService', $queueService);

        $publisher->prepareQueueForPublish($queue);
        $publisher->release();
    }

    public function dataReleaseWithDelay(): array
    {
        $queue1 = new QueueEntity('queue', 'exchange', 'StubJob', ['value' => 1]);

        $queue1->setScheduled(new \DateTime('21.01.2001'));

        return [
            [$queue1],
        ];
    }

    /**
     * @param QueueEntityInterface $queue
     * @param ErrorException       $exception
     * @param QueueEntityInterface $queueWaiting
     * @param DateTime             $dateTimeDelay
     *
     * @throws \Exception
     *
     * @dataProvider dataReleaseException
     */
    public function testReleaseException(
        QueueEntityInterface $queue,
        ErrorException $exception,
        QueueEntityInterface $queueWaiting,
        DateTime $dateTimeDelay
    ): void {
        $mockDelayService = $this->getMockDelayService(['delayQueue']);
        $mockDelayService->method('delayQueue')
            ->willReturnCallback(
                function (QueueEntityInterface $queue) use ($dateTimeDelay) {
                    return $queue->setWaiting($dateTimeDelay);
                }
            );

        $queueService = $this->getMockQueueService(['isTransactionActive', 'save']);

        $queueService->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn(false);

        $queueService->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$queue],
                [$queueWaiting]
            );

        $publisher = $this->getMockPublisher(['publishQueue']);

        $publisher->expects($this->once())
            ->method('publishQueue')
            ->with($queue)
            ->will($this->throwException($exception));

        Reflection::setProtectedProperties(
            $publisher,
            [
                'queueService' => $queueService,
                'logger' => $this->getMockLogger(),
                'delayService' => $mockDelayService,
            ]
        );

        $publisher->prepareQueueForPublish($queue);
        $publisher->release();

        $this->assertEquals(QueueEntityMappedSuperclass::STATUS_WAITING, $queue->getStatus());
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function dataReleaseException(): array
    {
        $queue = new QueueEntity('queue', 'exchange', 'StubJob', ['value' => 1]);

        $dateTimeDelay = new DateTime();
        $queue1 = clone $queue;
        $queue1->setWaiting($dateTimeDelay);

        return [
            [
                $queue,
                new ErrorException(),
                $queue1,
                $dateTimeDelay,
            ],
        ];
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function dataPublishQueue(): array
    {
        $routingKey = 'routing_key';
        $queue = new QueueEntity($routingKey, 'exchange', 'StubJob', ['value' => 1]);

        Reflection::setProtectedProperty($queue, 'id', 1);

        return [
            [$queue, '{"id":1}', $routingKey],
        ];
    }

    /**
     * @param QueueEntityInterface $queue
     * @param string               $expectedBody
     * @param string               $expectedRoutingKey
     *
     * @throws \Exception
     *
     * @dataProvider dataPublishQueue()
     */
    public function testPublishQueue(
        QueueEntityInterface $queue,
        string $expectedBody,
        string $expectedRoutingKey
    ): void {
        $producer = $this->getMockProducer(['publish']);
        $producer->expects($this->once())
            ->method('publish')
            ->with($expectedBody, $expectedRoutingKey, ['delivery_mode' => 2]);

        $publisher = $this->getMockPublisher();

        Reflection::setProtectedProperty($publisher, 'producer', $producer);
        Reflection::callProtectedMethod($publisher, 'publishQueue', $queue);
    }
}
