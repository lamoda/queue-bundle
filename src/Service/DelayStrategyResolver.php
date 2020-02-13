<?php
declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use Lamoda\QueueBundle\Exception\UnknownStrategyKeyException;
use Lamoda\QueueBundle\Strategy\Delay\DelayStrategyInterface;

class DelayStrategyResolver
{
    public const DEFAULT_STRATEGY = 'default_delay_strategy';

    /**
     * @var array
     */
    protected $handlers;

    /**
     * @var array
     */
    protected $queuesConfiguration;

    public function __construct(iterable $handlers, array $queuesConfiguration = [])
    {
        $this->handlers            = iterator_to_array($handlers);
        $this->queuesConfiguration = $queuesConfiguration;
    }

    /**
     * @param string $queueName
     *
     * @return DelayStrategyInterface
     * @throws UnknownStrategyKeyException
     */
    public function getStrategy(string $queueName): DelayStrategyInterface
    {
        $strategyKey = $this->queuesConfiguration[$queueName] ?? self::DEFAULT_STRATEGY;

        if (isset($this->handlers[$strategyKey])) {
            return $this->handlers[$strategyKey];
        }

        throw new UnknownStrategyKeyException(sprintf('Delay strategy with key: %s doesn\'t exist', $strategyKey));
    }

    public function getDefaultStrategy(): DelayStrategyInterface
    {
        return $this->handlers[self::DEFAULT_STRATEGY];
    }
}
