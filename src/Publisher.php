<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle;

use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Psr\Log\LoggerInterface;
use Throwable;

class Publisher
{
    /** @var Producer */
    protected $producer;

    /** @var QueueService */
    protected $queueService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DelayService */
    protected $delayService;

    /** @var QueueEntityInterface[] */
    protected $publishQueues = [];

    public function __construct(
        Producer $producer,
        QueueService $queueService,
        LoggerInterface $logger,
        DelayService $delayService
    ) {
        $this->producer = $producer;
        $this->queueService = $queueService;
        $this->logger = $logger;
        $this->delayService = $delayService;
    }

    /**
     * @param QueueInterface $queueable
     *
     * @throws UnexpectedValueException
     *
     * @return Publisher
     */
    public function prepareJobForPublish(QueueInterface $queueable): self
    {
        $queue = $this->queueService->createQueue($queueable);

        $this->prepareQueueForPublish($queue);

        $this->logger->info(
            'Queue was created',
            [
                'tracking_id' => $queue->getId(),
                'message' => $queue->getId(),
                'name' => $queue->getName(),
                'exchange' => $queue->getExchange(),
                'job_name' => $queue->getJobName(),
                'data' => $queue->getData(),
            ]
        );

        return $this;
    }

    public function prepareQueueForPublish(QueueEntityInterface $queue): self
    {
        $this->publishQueues[] = $queue;

        return $this;
    }

    protected function publishQueue(QueueEntityInterface $queueEntity, int $deliveryMode = 2): void
    {
        $this->producer->publish(
            json_encode(['id' => $queueEntity->getId()]),
            $queueEntity->getName(),
            ['delivery_mode' => $deliveryMode]
        );
    }

    public function release(): void
    {
        if (0 === count($this->publishQueues)) {
            return;
        }

        //if there is an active transaction some queue entities could be not in database
        if (!$this->queueService->isTransactionActive()) {
            $this->releaseQueues($this->publishQueues);
            $this->clearStorage();
        }
    }

    private function clearStorage(): void
    {
        $this->publishQueues = [];
    }

    /**
     * @param QueueEntityInterface[] $queues
     */
    private function releaseQueues(array $queues): void
    {
        foreach ($queues as $queue) {
            if ($queue->isScheduled()) {
                continue;
            }

            $queue->setNew();
            $this->queueService->save($queue);
            try {
                $this->publishQueue($queue);
            } catch (Throwable $e) {
                $this->delayService->delayQueue($queue);
                $this->queueService->save($queue);

                $this->logger->alert(
                    ConstantMessage::AMQP_BROKER_IS_DOWN,
                    [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
        }
    }
}
