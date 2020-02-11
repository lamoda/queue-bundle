<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Strategy\Delay;

use Codeception\Test\Unit;
use Lamoda\QueueBundle\Strategy\Delay\GeometricProgressionStrategy;

class GeometricProgressionStrategyTest extends Unit
{
    /**
     * @dataProvider dataGenerateDelay
     */
    public function testGenerateDelay(int $expectedDelay, int $startIntervalSec, float $multiplier, int $attempt): void
    {
        $strategy = new GeometricProgressionStrategy($startIntervalSec, $multiplier);

        $actualDelayInterval = $strategy->generateInterval($attempt);

        $this->assertEquals($expectedDelay, $actualDelayInterval->s);
    }

    public function dataGenerateDelay(): array
    {
        return [
            [1, 1, 1, 1],
            [4, 1, 2, 3],
            [4, 2, 2, 2],
            [480, 60, 2, 4],
            'float multiplier' => [938, 60, 2.5, 4],
        ];
    }
}
