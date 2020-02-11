<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Factory;

use JMS\Serializer\SerializerInterface;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
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

    /**
     * @throws UnexpectedValueException
     */
    public function createQueue(QueueInterface $queueable): QueueEntityInterface
    {
        $name = $queueable->getQueue();
        $exchange = $queueable->getExchange();
        $jobName = get_class($queueable);
        $data = $this->serializer->serialize($queueable, 'json');

        $data = json_decode($data, true);
        $jsonErrorCode = json_last_error();

        if (JSON_ERROR_NONE !== $jsonErrorCode) {
            throw new UnexpectedValueException('json_decode error: ' . json_last_error_msg(), $jsonErrorCode);
        }

        /** @var QueueEntityInterface $queueEntity */
        $queueEntity = new $this->entityClass($name, $exchange, $jobName, $data);

        if (null !== $queueable->getScheduleAt()) {
            $queueEntity->setScheduled($queueable->getScheduleAt());
        }

        return $queueEntity;
    }
}
