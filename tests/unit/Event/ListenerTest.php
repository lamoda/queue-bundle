<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Event;

use Lamoda\QueueBundle\Event\Listener;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use PHPUnit_Framework_TestCase;

class ListenerTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;

    public function testOnTransitionFinished(): void
    {
        $publisherFactory = $this->getMockPublisherFactory(['releaseAll']);
        $publisherFactory->expects($this->once())
            ->method('releaseAll');

        $listener = $this->createListener($publisherFactory);
        $listener->onTransitionFinished();
    }

    public function createListener(PublisherFactory $publisherFactory): Listener
    {
        return new Listener($publisherFactory);
    }
}
