<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use DateInterval;
use DateTime;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Strategy\Delay\DelayStrategyInterface;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use PHPUnit_Framework_TestCase;

class DelayServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataDelayQueue
     */
    public function testDelayQueue(
        QueueEntityInterface $queue,
        QueueEntityInterface $expectedQueue,
        DateTime $dateTime
    ): void {
        $strategy = $this->createStrategy();
        /** @var DelayService | \PHPUnit_Framework_MockObject_MockObject $delayService */
        $delayService = $this->getMockBuilder(DelayService::class)
            ->setConstructorArgs([$strategy])
            ->setMethods(['getStartDateTime'])
            ->getMock();
        $delayService->method('getStartDateTime')
            ->willReturn($dateTime);

        $actualQueue = $delayService->delayQueue($queue);
        $this->assertEquals($expectedQueue, $actualQueue);
    }

    /**
     * @throws \Exception
     */
    public function dataDelayQueue(): array
    {
        $strategy = $this->createStrategy();
        $dateTime = new DateTime();
        $queue = $this->createQueue();
        $expected = clone $queue;
        $expected->setWaiting($dateTime->add($strategy::getInterval()));

        return [
            [
                $queue,
                $expected,
                $dateTime,
            ],
        ];
    }

    private function createQueue(): QueueEntity
    {
        return new QueueEntity('queue', 'exchange', 'ClassJob', []);
    }

    private function createStrategy()
    {
        return new class() implements DelayStrategyInterface {
            public function generateInterval(int $iteration): DateInterval
            {
                return static::getInterval();
            }

            public static function getInterval(): DateInterval
            {
                return new DateInterval('PT1M');
            }
        };
    }
}
