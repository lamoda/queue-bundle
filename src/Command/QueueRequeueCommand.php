<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Command;

use Lamoda\QueueBundle\Service\QueueRequeueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRequeueCommand extends Command
{
    /** @var QueueRequeueService */
    private $requeueService;

    /** @var int */
    private $batchSize;

    public function __construct(QueueRequeueService $requeueService, int $batchSize)
    {
        parent::__construct();

        $this->requeueService = $requeueService;
        $this->batchSize = $batchSize;
    }

    protected function configure(): void
    {
        $this->setName('queue:requeue')
            ->setDescription('Requeue messages from db to queue server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->requeueService->restoreQueues($this->batchSize)) {
            $output->writeln('<error>Error, see details in logs</error>');

            return 1;
        }

        $output->writeln('<info>Success requeue messages from db to queue</info>');

        return 0;
    }
}
