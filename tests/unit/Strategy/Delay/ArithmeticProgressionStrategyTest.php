<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Strategy\Delay;

use Codeception\Test\Unit;
use Lamoda\QueueBundle\Strategy\Delay\ArithmeticProgressionStrategy;

class ArithmeticProgressionStrategyTest extends Unit
{
    /**
     * @dataProvider dataGenerateDelay
     */
    public function testGenerateDelay(int $expectedDelay, int $startIntervalSec, float $multiplier, int $attempt): void
    {
        $strategy = new ArithmeticProgressionStrategy($startIntervalSec, $multiplier);

        $actualDelayInterval = $strategy->generateInterval($attempt);

        $this->assertEquals($expectedDelay, $actualDelayInterval->s);
    }

    public function dataGenerateDelay(): array
    {
        return [
            [60, 60, 1600, 1],
            [1660, 60, 1600, 2],
            [3260, 60, 1600, 3],
            [4860, 60, 1600, 4],
            'float multiplier' => [6462, 60,  1600.5, 5],
        ];
    }
}
