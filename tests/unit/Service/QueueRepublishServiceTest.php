<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use Lamoda\QueueBundle\Factory\PublisherFactory;
use Lamoda\QueueBundle\Service\QueueRepublishService;
use Lamoda\QueueBundle\Service\QueueService;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\Reflection;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpKernel\Tests\Logger;

class QueueRepublishServiceTest extends PHPUnit_Framework_TestCase
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
                'getToRepublish',
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
            ->expects($this->once())
            ->method('flush');
        $mockQueueService
            ->expects($this->once())
            ->method('getToRepublish')
            ->willReturn($queueMessages);
        $mockPublisherFactory = $this->getMockPublisherFactory(['republish', 'releaseAll']);
        $mockPublisherFactory
            ->expects($this->exactly(count($queueMessages)))
            ->method('republish');
        $mockPublisherFactory
            ->expects($this->once())
            ->method('releaseAll');

        $queueRepublishService = $this->createService($mockPublisherFactory, $mockQueueService);

        $this->assertTrue($queueRepublishService->republishQueues(5));
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function dataRestoreQueues(): array
    {
        $queueEntity = $this->getQueueEntity();
        $queueEntity2 = $this->getQueueEntity();
        Reflection::setProtectedProperty($queueEntity, 'id', 1);
        Reflection::setProtectedProperty($queueEntity2, 'id', 2);

        return [
            [
                [
                    $queueEntity,
                    $queueEntity2,
                ],
            ],
        ];
    }

    /**
     * @param array $queueMessages
     *
     * @dataProvider dataBatchRestoreQueues
     */
    public function testBatchRestoreQueues(array $queueMessages): void
    {
        $mockQueueService = $this->getMockQueueService(
            [
                'getToRepublish',
                'beginTransaction',
                'commit',
                'flush',
                'isTransactionActive',
            ]
        );
        $mockQueueService
            ->expects($this->exactly(4))
            ->method('beginTransaction');
        $mockQueueService
            ->expects($this->exactly(4))
            ->method('commit');
        $mockQueueService
            ->expects($this->exactly(4))
            ->method('flush');

        $mockQueueService
            ->expects($this->exactly(4))
            ->method('getToRepublish')
            ->willReturnOnConsecutiveCalls(
                $queueMessages,
                $queueMessages,
                $queueMessages,
                []
            );

        $mockPublisherFactory = $this->getMockPublisherFactory(['republish', 'releaseAll']);
        $mockPublisherFactory
            ->expects($this->exactly(3))
            ->method('republish');
        $mockPublisherFactory
            ->expects($this->exactly(4))
            ->method('releaseAll');

        $queueRepublishService = $this->createService($mockPublisherFactory, $mockQueueService);

        $this->assertTrue($queueRepublishService->republishQueues(1));
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function dataBatchRestoreQueues(): array
    {
        $queueEntity = $this->getQueueEntity();
        Reflection::setProtectedProperty($queueEntity, 'id', 1);

        return [
            [
                [
                    $queueEntity,
                ],
            ],
        ];
    }

    public function testRestoreQueuesFailed(): void
    {
        $mockQueueService = $this->getMockQueueService(
            [
                'getToRepublish',
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
            ->method('isTransactionActive')
            ->willReturn(true);
        $mockQueueService
            ->expects($this->once())
            ->method('rollback');
        $mockQueueService
            ->expects($this->once())
            ->method('getToRepublish')
            ->will($this->throwException(new \Exception('Something broken')));
        $queueRepublishService = $this->createService($this->getMockPublisherFactory(), $mockQueueService);

        $this->assertFalse($queueRepublishService->republishQueues(5));
    }

    /**
     * @param array $queueMessages
     *
     * @dataProvider dataRestoreQueuesFailedOnPublisherReleaseFailed
     */
    public function testRestoreQueuesFailedOnPublisherReleaseFailed(array $queueMessages): void
    {
        $mockQueueService = $this->getMockQueueService(
            [
                'getToRepublish',
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
            ->expects($this->once())
            ->method('commit');
        $mockQueueService
            ->expects($this->once())
            ->method('flush');
        $mockQueueService
            ->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn(false);
        $mockQueueService
            ->expects($this->never())
            ->method('rollback');
        $mockQueueService
            ->expects($this->once())
            ->method('getToRepublish')
            ->willReturn($queueMessages);

        $publisherFactory = $this->getMockPublisherFactory(['republish', 'releaseAll']);
        $publisherFactory
            ->expects($this->once())
            ->method('releaseAll')
            ->will($this->throwException(new \Exception('Something broken')));

        $queueRepublishService = $this->createService($publisherFactory, $mockQueueService);

        $this->assertFalse($queueRepublishService->republishQueues(5));
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function dataRestoreQueuesFailedOnPublisherReleaseFailed(): array
    {
        $queueEntity = $this->getQueueEntity();
        $queueEntity2 = $this->getQueueEntity();
        Reflection::setProtectedProperty($queueEntity, 'id', 1);
        Reflection::setProtectedProperty($queueEntity2, 'id', 2);

        return [
            [
                [
                    $queueEntity,
                    $queueEntity2,
                ],
            ],
        ];
    }

    /**
     * @param string $jobName
     * @param string $exchange
     * @param string $queueName
     * @param array  $data
     *
     * @throws \Exception
     *
     * @return QueueEntity
     */
    protected function getQueueEntity(
        string $jobName = 'someJobName',
        string $exchange = 'exchange',
        string $queueName = 'some_queue_name',
        array $data = ['id' => 1]
    ): QueueEntity {
        $queue = new QueueEntity($queueName, $exchange, $jobName, $data);

        Reflection::setProtectedProperty($queue, 'id', 1);

        return $queue;
    }

    protected function createService(
        PublisherFactory $publisherFactory,
        QueueService $queueService
    ): QueueRepublishService {
        return new QueueRepublishService(
            $publisherFactory,
            $queueService,
            new Logger()
        );
    }
}
