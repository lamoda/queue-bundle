<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle;

use DateTime;

interface QueueInterface
{
    public function getQueue(): string;

    public function getExchange(): string;

    public function setQueue(string $queue);

    public function setExchange(string $queue);

    public function getScheduleAt(): ?DateTime;

    public function setScheduleAt(?DateTime $dateTime);
}
