<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Strategy\Delay;

use DateInterval;

interface DelayStrategyInterface
{
    public function generateInterval(int $iteration): DateInterval;
}
