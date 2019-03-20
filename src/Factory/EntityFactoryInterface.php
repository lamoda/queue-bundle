<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Factory;

use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\QueueInterface;

interface EntityFactoryInterface
{
    public function createQueue(QueueInterface $queueable): QueueEntityInterface;
}
