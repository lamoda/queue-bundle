<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Job;

use PHPUnit_Framework_TestCase;

class StubJobTest extends PHPUnit_Framework_TestCase
{
    public function testDefault(): void
    {
        $id = 1;
        $queue = 'queue';
        $exchange = 'exchange';

        $job = new StubJob($id);

        $this->assertEquals($id, $job->getId());
        $this->assertEquals($queue, $job->getQueue());
        $this->assertEquals($exchange, $job->getExchange());
    }

    public function testOverride(): void
    {
        $id = 1;
        $queue = 'another-queue';
        $exchange = 'another-exchange';

        $job = new StubJob($id);
        $job->setQueue($queue)
            ->setExchange($exchange);

        $this->assertEquals($id, $job->getId());
        $this->assertEquals($queue, $job->getQueue());
        $this->assertEquals($exchange, $job->getExchange());
    }
}
