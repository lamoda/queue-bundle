<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Event;

use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Symfony\Component\EventDispatcher\Event;

class QueueAttemptsReachedEvent extends Event
{
    public const NAME = 'queue.attempts.reached.event';

    /** @var QueueEntityInterface */
    protected $queue;

    public function __construct(QueueEntityInterface $queue)
    {
        $this->queue = $queue;
    }

    public function getQueue(): QueueEntityInterface
    {
        return $this->queue;
    }
}
