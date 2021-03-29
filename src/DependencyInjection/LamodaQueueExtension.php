<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class LamodaQueueExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('lamoda_queue.entity_class', $config['entity_class']);
        $container->setParameter('lamoda_queue.max_attempts', $config['max_attempts']);
        $container->setParameter('lamoda_queue.batch_size_per_republish', $config['batch_size_per_republish']);
        $container->setParameter('lamoda_queue.batch_size_per_requeue', $config['batch_size_per_requeue']);
        $container->setParameter('lamoda_queue.strategy_delay_geometric_progression_start_interval_sec', $config['strategy_delay_geometric_progression_start_interval_sec']);
        $container->setParameter('lamoda_queue.strategy_delay_geometric_progression_multiplier', $config['strategy_delay_geometric_progression_multiplier']);
        $container->setParameter('lamoda_queue.command_unexpected_end_script_timeout', $config['command_unexpected_end_script_timeout']);
        $container->setParameter('lamoda_queue.queues_configuration', $config['queues']);
    }
}
