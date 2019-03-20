<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\DependencyInjection\CompilerPass;

use Lamoda\QueueBundle\ConstantMessage;
use Lamoda\QueueBundle\Exception\RuntimeException;
use Lamoda\QueueBundle\Handler\JobHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class JobHandlerCompilerPass implements CompilerPassInterface
{
    private const QUEUE_HANDLER_TAG = 'queue.handler';

    public function process(ContainerBuilder $container): void
    {
        $jobToHandlerMapping = $this->buildMapping($container);
        $jobHandlerDefinition = $this->buildJobHandlerDefinition($jobToHandlerMapping);

        $container->setDefinition(JobHandler::class, $jobHandlerDefinition);
    }

    protected function buildMapping(ContainerBuilder $container): array
    {
        $jobToHandlerMapping = [];

        foreach ($container->findTaggedServiceIds(self::QUEUE_HANDLER_TAG) as $serviceId => $tags) {
            /** @var string[] $attributes */
            foreach ($tags as $attributes) {
                if (!isset($attributes['handle'])) {
                    throw new RuntimeException(
                        sprintf(ConstantMessage::JOB_HANDLER_COMPILE_ERROR, self::QUEUE_HANDLER_TAG)
                    );
                }

                $jobToHandlerMapping[$attributes['handle']] = $serviceId;
            }
        }

        return $jobToHandlerMapping;
    }

    protected function buildJobHandlerDefinition(array $jobToHandlerMapping): Definition
    {
        return new Definition(
            JobHandler::class,
            [
                new Reference('service_container'),
                $jobToHandlerMapping,
            ]
        );
    }
}
