<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Factory;

use JMS\Serializer\SerializerInterface;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\QueueInterface;

class EntityFactory implements EntityFactoryInterface
{
    /** @var SerializerInterface */
    protected $serializer;

    /** @var string */
    protected $entityClass;

    public function __construct(SerializerInterface $serializer, string $entityClass)
    {
        $this->serializer = $serializer;
        $this->entityClass = $entityClass;
    }

    public function createQueue(QueueInterface $queueable): QueueEntityInterface
    {
        $name = $queueable->getQueue();
        $exchange = $queueable->getExchange();
        $jobName = get_class($queueable);
        $data = $this->serializer->serialize($queueable, 'json');

        /** @var QueueEntityInterface $queueEntity */
        $queueEntity = new $this->entityClass($name, $exchange, $jobName, json_decode($data, true));

        if (null !== $queueable->getScheduleAt()) {
            $queueEntity->setScheduled($queueable->getScheduleAt());
        }

        return $queueEntity;
    }
}
