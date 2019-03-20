<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Job\Feature;

use JMS\Serializer\Annotation as JMS;
use DateTime;

trait Queueable
{
    /**
     * @var string
     *
     * @JMS\Exclude
     */
    protected $queue;

    /**
     * @var string
     *
     * @JMS\Exclude
     */
    protected $exchange;

    /**
     * @var DateTime | null
     *
     * @JMS\Exclude
     */
    protected $scheduleAt;

    public function getQueue(): string
    {
        return $this->queue ?? $this->getDefaultQueue();
    }

    public function setQueue(string $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    public function getExchange(): string
    {
        return $this->exchange ?? $this->getDefaultExchange();
    }

    public function setExchange(string $exchange)
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function getScheduleAt(): ?DateTime
    {
        return $this->scheduleAt ?? $this->getDefaultScheduleAt();
    }

    public function setScheduleAt(?DateTime $scheduleAt): void
    {
        $this->scheduleAt = $scheduleAt;
    }

    public function getDefaultScheduleAt(): ?DateTime
    {
        return null;
    }

    abstract public function getDefaultQueue(): string;

    abstract public function getDefaultExchange(): string;
}
