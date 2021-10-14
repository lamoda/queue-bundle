<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use Lamoda\QueueBundle\Exception\UnknownStrategyKeyException;
use Lamoda\QueueBundle\Strategy\Delay\ArithmeticProgressionStrategy;
use Lamoda\QueueBundle\Strategy\Delay\GeometricProgressionStrategy;
use PHPUnit_Framework_TestCase;

class DelayStrategyResolverTest extends PHPUnit_Framework_TestCase
{
    use DelayStrategyResolverTrait;

    public function testGetStrategy()
    {
        $checkingQueueName = 'queue';
        $strategiesByQueues = ['queue' => 'arithmetic_strategy_key'];

        $resolver = $this->createDelayStrategyResolver($strategiesByQueues);
        $strategy = $resolver->getStrategy($checkingQueueName);
        $this->assertInstanceOf(ArithmeticProgressionStrategy::class, $strategy);
        $this->assertEquals(100, $strategy->getStartInterval());
        $this->assertEquals(4, $strategy->getMultiplier());
    }

    public function testGetDefaultStrategy()
    {
        $checkingQueueName = 'queue';
        $strategiesByQueues = [];

        $resolver = $this->createDelayStrategyResolver($strategiesByQueues);
        $strategy = $resolver->getStrategy($checkingQueueName);
        $this->assertInstanceOf(GeometricProgressionStrategy::class, $strategy);
        $this->assertEquals(60, $strategy->getStartInterval());
        $this->assertEquals(3, $strategy->getMultiplier());
    }

    public function testFailGetDefaultStrategy()
    {
        $this->expectException(UnknownStrategyKeyException::class);
        $checkingQueueName = 'queue';
        $strategiesByQueues = ['queue' => 'unknown_strategy_key'];

        $resolver = $this->createDelayStrategyResolver($strategiesByQueues);
        $resolver->getStrategy($checkingQueueName);
    }
}
