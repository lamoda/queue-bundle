<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Lamoda\QueueBundle\Service\QueueRequeueService;
use Lamoda\QueueBundle\Service\QueueService;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpKernel\Log\Logger;

class QueueRequeueServiceTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;

    /**
     * @param array $queueMessages
     *
     * @dataProvider dataRestoreQueues
     */
    public function testRestoreQueues(array $queueMessages): void
    {
        $mockQueueService = $this->getMockQueueService(
            [
                'getToRestore',
                'beginTransaction',
                'commit',
                'flush',
                'isTransactionActive',
            ]
        );

        $mockQueueService
            ->expects($this->once())
            ->method('beginTransaction');
        $mockQueueService
            ->expects($this->once())
            ->method('commit');
        $mockQueueService
            ->expects($this->exactly(count($queueMessages)))
            ->method('flush');
        $mockQueueService
            ->expects($this->once())
            ->method('getToRestore')
            ->willReturn($queueMessages);

        $mockPublisherFactory = $this->getMockPublisherFactory(['requeue', 'releaseAll']);
        $mockPublisherFactory
            ->expects($this->exactly(count($queueMessages)))
            ->method('requeue');
        $mockPublisherFactory
            ->expects($this->once())
            ->method('releaseAll');

        $queueRequeueService = $this->createService($mockPublisherFactory, $mockQueueService);

        $this->assertTrue($queueRequeueService->restoreQueues(5));
    }

    public function dataRestoreQueues(): array
    {
        $queueEntity = $this->getQueueEntity();
        $queueEntity2 = $this->getQueueEntity();

        return [
            [
                [
                    $queueEntity,
                    $queueEntity2,
                ],
            ],
        ];
    }

    public function testRestoreQueuesFailed(): void
    {
        $mockQueueService = $this->getMockQueueService(
            [
                'getToRestore',
                'beginTransaction',
                'commit',
                'flush',
                'isTransactionActive',
                'rollback',
            ]
        );
        $mockQueueService
            ->expects($this->once())
            ->method('beginTransaction');
        $mockQueueService
            ->expects($this->never())
            ->method('commit');
        $mockQueueService
            ->expects($this->never())
            ->method('flush');
        $mockQueueService
            ->expects($this->once())
            ->method('rollback');
        $mockQueueService
            ->expects($this->once())
            ->method('getToRestore')
            ->will($this->throwException(new \Exception('Something broken')));
        $queueRequeueService = $this->createService($this->getMockPublisherFactory(), $mockQueueService);

        $this->assertFalse($queueRequeueService->restoreQueues(5));
    }

    /**
     * @param QueueEntityInterface $queue
     *
     * @dataProvider dataRequeue
     */
    public function testRequeue(QueueEntityInterface $queue): void
    {
        $mockPublisherFactory = $this->getMockPublisherFactory(['requeue', 'releaseAll']);
        $mockPublisherFactory
            ->expects($this->once())
            ->method('requeue');
        $mockPublisherFactory
            ->expects($this->once())
            ->method('releaseAll');

        $service = $this->createService($mockPublisherFactory, $this->getMockQueueService());

        $service->requeue($queue);
    }

    public function dataRequeue(): array
    {
        $queue = $this->getQueueEntity()
            ->setInProgress()
            ->setError();

        return [
            [$queue],
        ];
    }

    protected function getQueueEntity(
        string $jobName = 'someJobName',
        string $exchange = 'exchange',
        string $queueName = 'some_queue_name',
        array $data = ['id' => 1]
    ): QueueEntity {
        return new QueueEntity($queueName, $exchange, $jobName, $data);
    }

    protected function createService(
        PublisherFactory $publisherFactory,
        QueueService $queueService
    ): QueueRequeueService {
        return new QueueRequeueService(
            $publisherFactory,
            $queueService,
            new Logger()
        );
    }
}
