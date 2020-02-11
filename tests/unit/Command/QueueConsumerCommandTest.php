<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Command;

use Exception;
use Lamoda\QueueBundle\Command\QueueConsumerCommand;
use Lamoda\QueueBundle\Tests\Unit\QueueCommonServicesTrait;
use Lamoda\QueueBundle\Tests\Unit\Reflection;
use Lamoda\QueueBundle\Tests\Unit\SymfonyMockTrait;
use PhpAmqpLib\Exception\AMQPLogicException;
use PhpAmqpLib\Exception\AMQPOutOfRangeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit_Framework_TestCase;

class QueueConsumerCommandTest extends PHPUnit_Framework_TestCase
{
    use QueueCommonServicesTrait;
    use SymfonyMockTrait;

    /**
     * @throws Exception
     *
     * @dataProvider dataExecute
     */
    public function testExecute(string $name): void
    {
        $input = $this->getMockInput([
            'getArguments',
            'getArgument',
            'getOption',
        ]);
        $input->expects($this->once())
            ->method('getArgument')
            ->with('name')
            ->willReturn($name);
        $input->expects($this->once())
            ->method('getArguments')
            ->willReturn([$name]);

        $output = $this->getMockOutput();

        $logger = $this->getMockLogger(['debug']);
        $logger->expects($this->exactly(2))
            ->method('debug');

        $command = $this->getMockBuilder(QueueConsumerCommand::class)
            ->setConstructorArgs([
                $name,
                $logger,
            ])
            ->setMethods(['consume'])
            ->getMock();

        $command->expects($this->once())
            ->method('consume')
            ->with($input, $output);

        Reflection::callProtectedMethod($command, 'execute', $input, $output);
    }

    public function dataExecute(): array
    {
        return [
            ['queue-name1'],
        ];
    }

    /**
     * @throws Exception
     *
     * @dataProvider dataExecuteWithException
     */
    public function testExecuteWithError(int $count, Exception $exception): void
    {
        $name = 'queue-name';

        $input = $this->getMockInput([
            'getArguments',
            'getArgument',
        ]);
        $input->expects($this->once())
            ->method('getArgument')
            ->with('name')
            ->willReturn($name);
        $input->expects($this->once())
            ->method('getArguments')
            ->willReturn([$name]);
        $output = $this->getMockOutput();

        $logger = $this->getMockLogger([
            'error',
            'debug',
            'info',
        ]);
        $logger->expects($this->exactly(2))
            ->method('debug');
        $logger->expects($this->exactly($count))
            ->method('error');

        $command = $this->getMockBuilder(QueueConsumerCommand::class)
            ->setConstructorArgs([
                $name,
                $logger,
            ])
            ->setMethods(['consume'])
            ->getMock();

        $command->expects($this->once())
            ->method('consume')
            ->willThrowException($exception);

        Reflection::callProtectedMethod($command, 'execute', $input, $output);
    }

    public function dataExecuteWithException(): array
    {
        return [
            [
                0,
                new AMQPTimeoutException('test'),
            ],
            [
                1,
                new AMQPOutOfRangeException('test'),
            ],
            [
                1,
                new AMQPLogicException('test'),
            ],
        ];
    }

    /**
     * @throws Exception
     *
     * @dataProvider dataExecuteWithAlert
     */
    public function testExecuteWithAlert(int $count, Exception $exception, int $timeOut): void
    {
        $name = 'queue-name';

        $input = $this->getMockInput(
            [
                'getArguments',
                'getArgument',
            ]
        );
        $input->expects($this->once())
            ->method('getArgument')
            ->with('name')
            ->willReturn($name);
        $input->expects($this->once())
            ->method('getArguments')
            ->willReturn([$name]);
        $output = $this->getMockOutput();

        $logger = $this->getMockLogger(
            [
                'alert',
                'debug',
                'info',
            ]
        );
        $logger->expects($this->exactly(2))
            ->method('debug');
        $logger->expects($this->exactly($count))
            ->method('alert');

        $command = $this->getMockBuilder(QueueConsumerCommand::class)
            ->setConstructorArgs(
                [
                    $name,
                    $logger,
                ]
            )
            ->setMethods(['consume', 'wait'])
            ->getMock();

        $command->expects($this->once())
            ->method('consume')
            ->willThrowException($exception);
        $command->expects($this->once())
            ->method('wait')
            ->with($timeOut);
        Reflection::setProtectedProperties($command, ['unexpectedEndScriptTimeOut' => $timeOut]);

        Reflection::callProtectedMethod($command, 'execute', $input, $output);
    }

    public function dataExecuteWithAlert(): array
    {
        return [
            [
                1,
                new Exception('test'),
                60,
            ],
        ];
    }
}
