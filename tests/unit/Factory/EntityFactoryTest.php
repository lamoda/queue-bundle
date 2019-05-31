<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Factory;

use JMS\Serializer\SerializerInterface;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Factory\EntityFactory;
use Lamoda\QueueBundle\Job\AbstractJob;
use Lamoda\QueueBundle\Tests\Unit\Job\StubJob;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PHPUnit_Framework_TestCase;

class EntityFactoryTest extends PHPUnit_Framework_TestCase
{
    use SymfonyMockTrait;

    /**
     * @param AbstractJob          $job
     * @param QueueEntityInterface $expected
     *
     * @dataProvider dataCreateQueue()
     */
    public function testCreateQueue(AbstractJob $job, QueueEntityInterface $expected): void
    {
        $serializer = $this->getJMSSerializer(['serialize']);
        $serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"id":1}');

        $factory = $this->createFactory($serializer);

        $this->assertEquals($expected, $factory->createQueue($job));
    }

    public function dataCreateQueue(): array
    {
        return [
            [
                new StubJob(1),
                new QueueEntity('queue', 'exchange', StubJob::class, ['id' => 1]),
            ],
            [
                (function () {
                    $job = new StubJob(1);
                    $job->setScheduleAt(new \DateTime('25.01.10'));

                    return $job;
                })(),
                (function () {
                    $queue = new QueueEntity('queue', 'exchange', StubJob::class, ['id' => 1]);
                    $queue->setScheduled(new \DateTime('25.01.10'));

                    return $queue;
                })(),
            ],
        ];
    }

    public function testJsonDecodeError(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionCode(4);
        $this->expectExceptionMessage('json_decode error: Syntax error');

        $serializer = $this->getJMSSerializer(['serialize']);
        $serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('abrakadabra');

        $factory = $this->createFactory($serializer);
        $factory->createQueue(new StubJob(1));
    }

    private function createFactory(SerializerInterface $serializer): EntityFactory
    {
        return new EntityFactory($serializer, QueueEntity::class);
    }
}
