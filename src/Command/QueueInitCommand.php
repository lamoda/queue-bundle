<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Command;

use OldSound\RabbitMqBundle\Command\SetupFabricCommand;

class QueueInitCommand extends SetupFabricCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('queue:init')
            ->setDescription('Declare during deploy exchanges and queues');
    }
}
