<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use DateTime;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\UnknownStrategyKeyException;
use Psr\Log\LoggerInterface;

class DelayService
{
    /**
     * @var DelayStrategyResolver
     */
    protected $strategyService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DelayStrategyResolver $strategyService, LoggerInterface $logger)
    {
        $this->strategyService = $strategyService;
        $this->logger          = $logger;
    }

    public function delayQueue(QueueEntityInterface $queue): QueueEntityInterface
    {
        try {
            $strategy = $this->strategyService->getStrategy($queue->getName());
        } catch (UnknownStrategyKeyException $exception) {
            $this->logger->warning(
                $exception->getMessage(), [
                    'queue_name' => $queue->getName(),
                ]
            );
            $strategy = $this->strategyService->getDefaultStrategy();
        }

        $iteration        = $queue->getAttempts() ?? 1;
        $newDelayInterval = $strategy->generateInterval($iteration);
        $delayUntil       = $this->getStartDateTime()->add($newDelayInterval);

        $queue->setWaiting($delayUntil);

        return $queue;
    }

    protected function getStartDateTime(): DateTime
    {
        return new DateTime();
    }
}
