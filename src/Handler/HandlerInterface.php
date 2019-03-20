<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Handler;

use Lamoda\QueueBundle\QueueInterface;

interface HandlerInterface
{
    public function handle(QueueInterface $job);
}
