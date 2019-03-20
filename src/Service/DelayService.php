<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use DateTime;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Strategy\Delay\DelayStrategyInterface;

class DelayService
{
    /**
     * @var DelayStrategyInterface
     */
    protected $strategy;

    public function __construct(DelayStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function delayQueue(QueueEntityInterface $queue): QueueEntityInterface
    {
        $iteration = $queue->getAttempts() ?? 1;
        $newDelayInterval = $this->strategy->generateInterval($iteration);
        $delayUntil = $this->getStartDateTime()->add($newDelayInterval);

        $queue->setWaiting($delayUntil);

        return $queue;
    }

    protected function getStartDateTime(): DateTime
    {
        return new DateTime();
    }
}
