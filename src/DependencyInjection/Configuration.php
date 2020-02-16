<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lamoda_queue');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('entity_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('max_attempts')
                    ->isRequired()
                ->end()
                ->integerNode('batch_size_per_republish')
                    ->isRequired()
                ->end()
                ->integerNode('batch_size_per_requeue')
                    ->isRequired()
                ->end()
                ->integerNode('strategy_delay_geometric_progression_start_interval_sec')
                    ->defaultValue(60)
                ->end()
                ->integerNode('strategy_delay_geometric_progression_multiplier')
                    ->defaultValue(2)
                ->end()
                ->integerNode('command_unexpected_end_script_timeout')
                    ->defaultValue(0)
                ->end()
            ->end();

        $this->addQueues($rootNode);

        return $treeBuilder;
    }

    protected function addQueues(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('queues')
                ->canBeUnset()
                ->useAttributeAsKey('key')
                ->treatNullLike([])
                ->prototype('scalar')->end()
            ->end()
        ;
    }
}
