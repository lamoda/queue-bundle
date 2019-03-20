<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Command;

use Lamoda\QueueBundle\Command\QueueRequeueCommand;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\Reflection;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PHPUnit_Framework_TestCase;

class QueueRequeueCommandTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    public function testCreate(): void
    {
        $requeueService = $this->getMockQueueRequeueService();
        $command = new QueueRequeueCommand($requeueService, 1);

        $this->assertEquals('queue:requeue', $command->getName());
    }

    public function dataExecute(): array
    {
        return [
            [
                true,
                0,
            ],
            [
                false,
                1,
            ],
        ];
    }

    /**
     * @param bool $restored
     * @param int  $expected
     *
     * @throws \Exception
     *
     * @dataProvider dataExecute
     */
    public function testExecute(bool $restored, int $expected): void
    {
        $requeueService = $this->getMockQueueRequeueService(['restoreQueues']);
        $requeueService->expects($this->once())
            ->method('restoreQueues')
            ->willReturn($restored);

        $command = new QueueRequeueCommand($requeueService, 1);

        $this->assertEquals(
            $expected,
            Reflection::callProtectedMethod($command, 'execute', $this->getMockInput(), $this->getMockOutput())
        );
    }
}
