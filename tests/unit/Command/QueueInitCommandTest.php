<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Command;

use Lamoda\QueueBundle\Command\QueueInitCommand;
use PHPUnit_Framework_TestCase;

class QueueInitCommandTest extends PHPUnit_Framework_TestCase
{
    public function testCreate(): void
    {
        $command = new QueueInitCommand();

        $this->assertEquals('queue:init', $command->getName());
    }
}
