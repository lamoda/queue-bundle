<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Command;

use Lamoda\QueueBundle\ConstantMessage;
use OldSound\RabbitMqBundle\Command\BaseConsumerCommand as OldSoundConsumerCommand;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class QueueConsumerCommand extends OldSoundConsumerCommand
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var int */
    protected $unexpectedEndScriptTimeOut;

    public function __construct(string $name, LoggerInterface $logger, int $unexpectedEndScriptTimeOut = 0)
    {
        parent::__construct($name);

        $this->logger = $logger;
        $this->unexpectedEndScriptTimeOut = $unexpectedEndScriptTimeOut;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Run the consumer');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $arguments = $input->getArguments();
        $consumerName = $input->getArgument('name');

        $this->logger->debug(sprintf('Start consumer %s', $consumerName), $arguments);

        try {
            $this->consume($input, $output);
        } catch (AMQPTimeoutException $e) {
            $this->logger->info(
                ConstantMessage::AMQP_TIMEOUT_ERROR,
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        } catch (AMQPExceptionInterface $e) {
            $this->logger->error(
                ConstantMessage::AMQP_ERROR,
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        } catch (Throwable $e) {
            $this->logger->alert(
                ConstantMessage::QUEUE_CONSUMER_COMMAND_UNEXPECTED_ERROR,
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            $this->wait($this->unexpectedEndScriptTimeOut);
        }
        $this->logger->debug(sprintf('Stop consumer %s', $consumerName), $arguments);
    }

    protected function consume(InputInterface $input, OutputInterface $output): void
    {
        parent::execute($input, $output);
    }

    protected function getConsumerService(): string
    {
        return 'old_sound_rabbit_mq.%s_consumer';
    }

    protected function wait(int $time): void
    {
        sleep($time);
    }
}
