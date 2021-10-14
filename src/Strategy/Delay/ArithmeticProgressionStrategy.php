<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Strategy\Delay;

use DateInterval;

class ArithmeticProgressionStrategy implements DelayStrategyInterface
{
    /** @var int */
    private $startInterval;

    /** @var float */
    private $multiplier;

    public function __construct(int $startIntervalSec, float $multiplier)
    {
        $this->startInterval = $startIntervalSec;
        $this->multiplier    = $multiplier;
    }

    public function getStartInterval(): int
    {
        return $this->startInterval;
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }

    public function generateInterval(int $iteration): DateInterval
    {
        $newIntervalSec = (int) ceil($this->startInterval + ($this->multiplier * ($iteration - 1)));

        return new DateInterval('PT' . $newIntervalSec . 'S');
    }
}
