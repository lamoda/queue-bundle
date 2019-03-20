<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Event;

use Lamoda\QueueBundle\Factory\PublisherFactory;

class Listener
{
    /** @var PublisherFactory */
    protected $publisherFactory;

    public function __construct(PublisherFactory $publisherFactory)
    {
        $this->publisherFactory = $publisherFactory;
    }

    public function onTransitionFinished(): void
    {
        $this->publisherFactory->releaseAll();
    }
}
