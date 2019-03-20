<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle;

use Lamoda\QueueBundle\DependencyInjection\CompilerPass\JobHandlerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LamodaQueueBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new JobHandlerCompilerPass());
    }
}
