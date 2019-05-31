<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Factory;

use Lamoda\QueueBundle\ConstantMessage;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\RuntimeException;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Publisher;
use Lamoda\QueueBundle\QueueInterface;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PublisherFactory
{
    /** @var ContainerInterface */
    protected $serviceContainer;

    /** @var QueueService */
    protected $queueService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DelayService */
    protected $delayService;

    /** @var Publisher[] */
    private $publishers = [];

    public function __construct(
        ContainerInterface $serviceContainer,
        QueueService $queueService,
        LoggerInterface $logger,
        DelayService $delayService
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->queueService = $queueService;
        $this->logger = $logger;
        $this->delayService = $delayService;
    }

    protected function create(string $exchangeName): Publisher
    {
        $producerServiceId = $this->getProducerServiceId($exchangeName);

        if (!$this->serviceContainer->has($producerServiceId)) {
            throw new RuntimeException(sprintf(ConstantMessage::PUBLISHER_NOT_FOUND, $exchangeName));
        }

        /** @var Producer $producer */
        $producer = $this->serviceContainer->get($producerServiceId);

        return new Publisher($producer, $this->queueService, $this->logger, $this->delayService);
    }

    public function get(string $exchangeName): Publisher
    {
        if (!isset($this->publishers[$exchangeName]) || !($this->publishers[$exchangeName] instanceof Publisher)) {
            $this->publishers[$exchangeName] = $this->create($exchangeName);
        }

        return $this->publishers[$exchangeName];
    }

    /**
     * @param QueueInterface $queueable
     *
     * @throws UnexpectedValueException
     */
    public function publish(QueueInterface $queueable): void
    {
        $exchangeName = $queueable->getExchange();

        $this->get($exchangeName)->prepareJobForPublish($queueable);
    }

    public function requeue(QueueEntityInterface $queue): void
    {
        $exchangeName = $queue->getExchange();

        $queue->reset();

        $this->get($exchangeName)->prepareQueueForPublish($queue);
    }

    public function republish(QueueEntityInterface $queue): void
    {
        $exchangeName = $queue->getExchange();

        $queue->setNew();

        $this->get($exchangeName)->prepareQueueForPublish($queue);
    }

    public function releaseAll(): void
    {
        foreach ($this->publishers as $publisher) {
            $publisher->release();
        }
    }

    private function getProducerServiceId(string $exchangeName): string
    {
        return sprintf('old_sound_rabbit_mq.%s_producer', $exchangeName);
    }
}
