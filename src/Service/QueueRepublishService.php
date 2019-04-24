<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use Lamoda\QueueBundle\ConstantMessage;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class QueueRepublishService
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

    public function republishQueues(int $batchSize): bool
    {
        $republishedQueueIds = [];
        do {
            $this->queueService->beginTransaction();

            try {
                $queues = $this->queueService->getToRepublish($batchSize);
                if ($queues) {
                    foreach ($queues as $queue) {
                        $this->publisherFactory->republish($queue);
                        $republishedQueueIds[] = $queue->getId();
                    }
                }

                $this->queueService->flush();
                $this->queueService->commit();
                $this->publisherFactory->releaseAll();
            } catch (Throwable $exception) {
                if ($this->queueService->isTransactionActive()) {
                    $this->queueService->rollback();
                }

                $this->logger->error(
                    ConstantMessage::QUEUE_CAN_NOT_REPUBLISH,
                    [
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                    ]
                );

                return false;
            }
        } while (count($queues) === $batchSize);

        if ($republishedQueueIds) {
            $this->logger->info(
                ConstantMessage::QUEUE_SUCCESS_REPUBLISH,
                ['queuesIds' => implode(', ', $republishedQueueIds)]
            );
        }

        return true;
    }
}
