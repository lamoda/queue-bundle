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
        $this->assertAttributeEquals(100, 'startInterval', $strategy);
        $this->assertAttributeEquals(4, 'multiplier', $strategy);
    }

    public function testGetDefaultStrategy()
    {
        $checkingQueueName = 'queue';
        $strategiesByQueues = [];

        $resolver = $this->createDelayStrategyResolver($strategiesByQueues);
        $strategy = $resolver->getStrategy($checkingQueueName);
        $this->assertInstanceOf(GeometricProgressionStrategy::class, $strategy);
        $this->assertAttributeEquals(60, 'startInterval', $strategy);
        $this->assertAttributeEquals(3, 'multiplier', $strategy);
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
