<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use Lamoda\QueueBundle\ConstantMessage;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class QueueRequeueService
{
    /** @var PublisherFactory */
    protected $publisherFactory;

    /** @var QueueService */
    protected $queueService;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        PublisherFactory $publisherFactory,
        QueueService $queueService,
        LoggerInterface $logger
    ) {
        $this->publisherFactory = $publisherFactory;
        $this->queueService = $queueService;
        $this->logger = $logger;
    }

    public function restoreQueues(int $batchSize): bool
    {
        $this->queueService->beginTransaction();

        try {
            do {
                $queues = $this->queueService->getToRestore($batchSize);
                if ($queues) {
                    foreach ($queues as $queue) {
                        $this->publisherFactory->requeue($queue);
                        $this->queueService->flush($queue);
                    }
                }
                $this->queueService->commit();
                $this->publisherFactory->releaseAll();
            } while (count($queues) === $batchSize);
        } catch (Throwable $exception) {
            $this->queueService->rollback();

            $this->logger->error(
                ConstantMessage::QUEUE_CAN_NOT_REQUEUE,
                [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]
            );

            return false;
        }

        return true;
    }

    public function requeue(QueueEntityInterface $queue): void
    {
        $this->publisherFactory->requeue($queue);
        $this->publisherFactory->releaseAll();
    }
}
