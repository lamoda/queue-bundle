<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Factory;

use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Lamoda\QueueBundle\Tests\Unit\Job\StubJob;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\Reflection;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PHPUnit_Framework_TestCase;

class PublisherFactoryTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    /**
     * @throws \Exception
     */
    public function testPublish(): void
    {
        $job = new StubJob(1);

        $publisher = $this->getMockPublisher(['prepareJobForPublish']);
        $publisher->expects($this->once())
            ->method('prepareJobForPublish')
            ->with($job);

        $factory = $this->createFactory();
        Reflection::setProtectedProperty($factory, 'publishers', ['exchange' => $publisher]);

        $factory->publish($job);
    }

    /**
     * @throws \Exception
     */
    public function testRestore(): void
    {
        $queue = new QueueEntity('queue', 'exchange', 'ClassJob', ['id' => 1]);
        $queue->setInProgress();

        $factory = $this->createFactory();
        Reflection::setProtectedProperty($factory, 'publishers', ['exchange' => $this->getMockPublisher()]);

        $factory->requeue($queue);
        $this->assertEquals(QueueEntityMappedSuperclass::STATUS_INITIAL, $queue->getStatus());
    }

    /**
     * @throws \Exception
     */
    public function testReleaseAll(): void
    {
        $publisher = $this->getMockPublisher(['release']);
        $publisher->expects($this->once())
            ->method('release');

        $factory = $this->createFactory();
        Reflection::setProtectedProperty($factory, 'publishers', [$publisher]);

        $factory->releaseAll();
    }

    private function createFactory(): PublisherFactory
    {
        return new PublisherFactory(
            $this->getMockServiceContainer(),
            $this->getMockQueueService(),
            $this->getMockLogger(),
            $this->getMockDelayService()
        );
    }
}
