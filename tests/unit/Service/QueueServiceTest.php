<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use DateTime;
use Lamoda\QueueBundle\Entity\QueueRepository;
use Lamoda\QueueBundle\Event\QueueAttemptsReachedEvent;
use Lamoda\QueueBundle\Exception\AttemptsReachedException;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Factory\EntityFactory;
use Lamoda\QueueBundle\Publisher;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\QueueService;
use Lamoda\QueueBundle\Tests\Unit\Job\StubJob;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class QueueServiceTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    /** @var int */
    protected $maxAttempts = 5;

    /**
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function testGetToRestore(): void
    {
        $limit = 10;

        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('getToRestore')
            ->with($this->maxAttempts, $limit);

        $this->createService($queueRepository)->getToRestore($limit);
    }

    /**
     * @param QueueEntity $queue
     *
     * @throws \Exception
     *
     * @dataProvider dataGetToProcess()
     */
    public function testGetToProcess(QueueEntity $queue): void
    {
        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($queue);
        $queueRepository
            ->expects($this->once())
            ->method('save')
            ->with($queue);

        $this->assertEquals($queue, $this->createService($queueRepository)->getToProcess(1));
        $this->assertEquals(QueueEntity::STATUS_IN_PROGRESS_TITLE, $queue->getStatusAsString());
    }

    public function dataGetToProcess(): array
    {
        return [
            'new status' => [(new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]))->setNew()],
            'in progress status' => [(new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]))->setInProgress()],
        ];
    }

    /**
     * @param null | QueueEntity $queueEntity
     * @param string $expectedExceptionMessage
     *
     * @throws \Exception
     *
     * @dataProvider dataGetToProcessQueueNotFound
     */
    public function testGetToProcessQueueNotFound(?QueueEntity $queueEntity, string $expectedExceptionMessage): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $queueRepository = $this->getQueueRepository();

        $queueRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($queueEntity);

        $this->createService($queueRepository)->getToProcess(1);
    }

    public function dataGetToProcessQueueNotFound(): array
    {
        return [
            'Not found' => [
                null,
                'The queue with id "1" was not found',
            ],
            'Status not NEW' => [
                new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]),
                'The queue "queue" with job "ClassJob" was not found in suitable status. Actual status is "initial"',
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testGetToProcessAttemptsReached(): void
    {
        $this->expectException(AttemptsReachedException::class);

        $queue = new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]);
        $queue->setNew();

        $this->maxAttempts = 0;

        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($queue);
        $queueRepository
            ->expects($this->once())
            ->method('save')
            ->with($queue);

        $attemptsReachedEvent = new QueueAttemptsReachedEvent($queue);

        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('queue.attempts.reached.event', $attemptsReachedEvent);

        $this->assertEquals(
            $queue,
            $this->createService($queueRepository, null, $eventDispatcher)->getToProcess(1)
        );
        $this->assertEquals(QueueEntity::STATUS_ATTEMPTS_REACHED_TITLE, $queue->getStatusAsString());
    }

    public function testSave(): void
    {
        $queue = new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]);

        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('save')
            ->with($queue)
            ->willReturnArgument(0);

        $this->assertEquals(
            $queue,
            $this->createService($queueRepository)->save($queue)
        );
    }

    public function testCreateQueue(): void
    {
        $job = new StubJob(1);
        $queue = new QueueEntity('queue', 'exchange', StubJob::class, ['id' => 1]);

        $entityFactory = $this->getEntityFactory();
        $entityFactory
            ->expects($this->once())
            ->method('createQueue')
            ->with($job)
            ->willReturn($queue);

        $queueRepository = $this->getQueueRepository();

        $this->assertEquals(
            $queue,
            $this->createService($queueRepository, $entityFactory)->createQueue($job)
        );
    }

    public function testSavingScheduledQueue(): void
    {
        $dateTime = new DateTime();
        $job = new StubJob(1);
        $queue = new QueueEntity('queue', 'exchange', StubJob::class, ['id' => 1]);
        $queue->setScheduled($dateTime);

        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('save')
            ->with($queue)
            ->willReturnArgument(0);

        $entityFactory = $this->getEntityFactory();
        $entityFactory
            ->expects($this->once()) #at least once
            ->method('createQueue')
            ->willReturn($queue);

        $queueService = $this->createService($queueRepository, $entityFactory);
        $publisher = new Publisher(
            $this->createMock(Producer::class),
            $queueService,
            new NullLogger(),
            $this->createMock(DelayService::class)
        );

        $createdQueue = $queueService->createQueue($job);
        $publisher->prepareQueueForPublish($createdQueue);
        $publisher->release();
    }
    
    public function testIsTransactionActive(): void
    {
        $expected = true;

        $queueRepository = $this->getQueueRepository();
        $queueRepository
            ->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->createService($queueRepository)->isTransactionActive()
        );
    }

    private function createService(
        ?QueueRepository $repository = null,
        ?EntityFactory $entityFactory = null,
        ?EventDispatcher $eventDispatcher = null
    ): QueueService {
        return new QueueService(
            $repository ?? $this->getQueueRepository(),
            $entityFactory ?? $this->getEntityFactory(),
            $eventDispatcher ?? $this->getEventDispatcher(),
            $this->maxAttempts
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueRepository
     */
    private function getQueueRepository()
    {
        return $this->getMockQueueRepository(
            [
                'find',
                'getToRestore',
                'save',
                'isTransactionActive',
            ]
        );
    }

    /**
     * @return EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEntityFactory()
    {
        return $this->getMockEntityFactory(['createQueue']);
    }

    /**
     * @return EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEventDispatcher()
    {
        return $this->getMockEventDispatcher(['dispatch']);
    }
}
