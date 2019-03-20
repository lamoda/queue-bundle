<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Command;

use Lamoda\QueueBundle\Service\QueueRepublishService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRepublishCommand extends Command
{
    /** @var QueueRepublishService */
    private $queueRepublishService;

    /** @var int */
    private $batchSize;

    public function __construct(QueueRepublishService $queueRepublishService, int $batchSize)
    {
        parent::__construct();

        $this->queueRepublishService = $queueRepublishService;
        $this->batchSize = $batchSize;
    }

    protected function configure(): void
    {
        $this->setName('queue:republish')
            ->setDescription('Republish messages from db to queue server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->queueRepublishService->republishQueues($this->batchSize)) {
            $output->writeln('<error>Error, see details in logs</error>');

            return 1;
        }

        $output->writeln('<info>Success republish messages from db to queue</info>');

        return 0;
    }
}
