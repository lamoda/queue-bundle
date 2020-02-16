<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use DateInterval;
use DateTime;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\DelayStrategyResolver;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;

class DelayServiceTest extends PHPUnit_Framework_TestCase
{
    use SymfonyMockTrait;
    use DelayStrategyResolverTrait;

    /**
     * @dataProvider dataDelayQueue
     */
    public function testDelayQueue(
        QueueEntityInterface $queue,
        QueueEntityInterface $expectedQueue,
        DateTime $dateTime,
        DelayStrategyResolver $strategyService,
        LoggerInterface $logger
    ): void {
        /** @var DelayService | \PHPUnit_Framework_MockObject_MockObject $delayService */
        $delayService = $this->getMockBuilder(DelayService::class)
            ->setConstructorArgs([$strategyService, $logger])
            ->setMethods(['getStartDateTime'])
            ->getMock();
        $delayService->method('getStartDateTime')
            ->willReturn($dateTime);

        $actualQueue = $delayService->delayQueue($queue);
        $this->assertEquals($expectedQueue, $actualQueue);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function dataDelayQueue(): \Generator
    {
        $strategyService = $this->createDelayStrategyResolver([]);
        $logger = $this->getMockLogger();
        $dateTime = new DateTime();
        $queue = $this->createQueue();
        $expected = clone $queue;
        $expected->setWaiting($dateTime->add(new DateInterval('PT60S')));

        yield 'With valid strategy key' => [
            $queue,
            $expected,
            $dateTime,
            $strategyService,
            $logger,
        ];

        $strategyService = $this->createDelayStrategyResolver([$queue->getName() => 'unknown_strategy_key']);
        $logger = $this->getMockLogger(['warning']);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                'Delay strategy with key: unknown_strategy_key doesn\'t exist', [
                'queue_name' => $queue->getName(),
            ]
            );
        $dateTime = new DateTime();
        $queue = $this->createQueue();
        $expected = clone $queue;
        $expected->setWaiting($dateTime->add(new DateInterval('PT60S')));

        yield 'With invalid strategy key warning' => [
            $queue,
            $expected,
            $dateTime,
            $strategyService,
            $logger,
        ];
    }

    private function createQueue(): QueueEntity
    {
        return new QueueEntity('queue', 'exchange', 'ClassJob', []);
    }
}
