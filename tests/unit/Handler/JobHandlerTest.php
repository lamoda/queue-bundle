<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Handler;

use Lamoda\QueueBundle\Exception\MissingHandlerException;
use Lamoda\QueueBundle\Handler\JobHandler;
use Lamoda\QueueBundle\Tests\Unit\Job\StubJob;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobHandlerTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    public function testHandle(): void
    {
        $job = new StubJob(1);
        $serviceId = 'serviceId';

        $handler = $this->getMockHandler(['handle']);

        $container = $this->getMockServiceContainer(['get']);
        $container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->willReturn($handler);

        $registry = $this->createRegistry($container, [get_class($job) => $serviceId]);
        $registry->handle($job);
    }

    public function testHandlerMissingHandlerException(): void
    {
        $this->expectException(MissingHandlerException::class);

        $job = new StubJob(1);

        $container = $this->getMockServiceContainer(['get']);
        $container->expects($this->never())
            ->method('get');

        $registry = $this->createRegistry($container, []);
        $registry->handle($job);
    }

    private function createRegistry(ContainerInterface $container, array $jobToServiceIdMapping): JobHandler
    {
        return new JobHandler($container, $jobToServiceIdMapping);
    }
}
