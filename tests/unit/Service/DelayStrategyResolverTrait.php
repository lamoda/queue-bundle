<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Service;

use Lamoda\QueueBundle\Service\DelayStrategyResolver;
use Lamoda\QueueBundle\Strategy\Delay\ArithmeticProgressionStrategy;
use Lamoda\QueueBundle\Strategy\Delay\GeometricProgressionStrategy;

trait DelayStrategyResolverTrait
{
    private function createDelayStrategyResolver(array $strategiesByQueues): DelayStrategyResolver
    {
        $arithmeticStrategy = new ArithmeticProgressionStrategy(100, 4);
        $geometricStrategy = new GeometricProgressionStrategy(30, 2);
        $defaultStrategy = new GeometricProgressionStrategy(60, 3);

        $strategies = [
            'arithmetic_strategy_key' => $arithmeticStrategy,
            'geometric_strategy_key' => $geometricStrategy,
            'default_delay_strategy' => $defaultStrategy,
        ];

        return new DelayStrategyResolver($this->createStrategiesList($strategies), $strategiesByQueues);
    }

    private function createStrategiesList(array $strategies)
    {
        return new class($strategies) implements \Iterator {
            /**
             * @var array
             */
            protected $strategies;

            public function __construct(array $strategies)
            {
                $this->strategies = $strategies;
            }

            public function current()
            {
                return current($this->strategies);
            }

            public function next()
            {
                return next($this->strategies);
            }

            public function key()
            {
                return key($this->strategies);
            }

            public function valid()
            {
                return null !== key($this->strategies);
            }

            public function rewind()
            {
                return reset($this->strategies);
            }
        };
    }
}
