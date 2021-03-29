<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Service;

use Exception;
use Lamoda\QueueBundle\ConstantMessage;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;
use Lamoda\QueueBundle\Entity\QueueRepository;
use Lamoda\QueueBundle\Event\QueueAttemptsReachedEvent;
use Lamoda\QueueBundle\Exception\AttemptsReachedException;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Factory\EntityFactoryInterface;
use Lamoda\QueueBundle\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueueService
{
    /** @var QueueRepository */
    protected $repository;

    /** @var EntityFactoryInterface */
    protected $entityFactory;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var int */
    protected $maxAttempts;

    /** @var array */
    protected $queueSuitableStatuses = [
        QueueEntityMappedSuperclass::STATUS_NEW,
        QueueEntityMappedSuperclass::STATUS_IN_PROGRESS,
    ];

    public function __construct(
        QueueRepository $repository,
        EntityFactoryInterface $entityFactory,
        EventDispatcherInterface $eventDispatcher,
        int $maxAttempts
    ) {
        $this->repository = $repository;
        $this->entityFactory = $entityFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @param int | null $offset
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return array|QueueEntityInterface[]
     */
    public function getToRestore(int $limit, ?int $offset = null): array
    {
        return $this->repository->getToRestore($this->maxAttempts, $limit, $offset);
    }

    /**
     * @throws Exception
     */
    public function getToProcess(int $id): QueueEntityInterface
    {
        $queueEntity = $this->repository->find($id);

        if (!($queueEntity instanceof QueueEntityInterface)) {
            throw new UnexpectedValueException(sprintf(ConstantMessage::QUEUE_ENTITY_NOT_FOUND, $id));
        }

        if (!in_array($queueEntity->getStatus(), $this->queueSuitableStatuses, true)) {
            throw new UnexpectedValueException(sprintf(
                ConstantMessage::QUEUE_ENTITY_NOT_FOUND_IN_SUITABLE_STATUS,
                $queueEntity->getName(),
                $queueEntity->getJobName(),
                $queueEntity->getStatusAsString()
            ));
        }

        $attemptsReached = $queueEntity->isMaxAttemptsReached($this->maxAttempts);
        if ($attemptsReached) {
            $queueEntity->setAttemptsReached();
        } else {
            $queueEntity->setInProgress();
        }

        $this->repository->save($queueEntity);

        if ($attemptsReached) {
            $this->eventDispatcher->dispatch(QueueAttemptsReachedEvent::NAME, new QueueAttemptsReachedEvent($queueEntity));

            throw new AttemptsReachedException(sprintf(ConstantMessage::QUEUE_ATTEMPTS_REACHED, $queueEntity->getName()));
        }

        return $queueEntity;
    }

    /**
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return array|QueueEntityInterface[]
     */
    public function getToRepublish(int $limit, ?int $offset = null): array
    {
        return $this->repository->getToRepublish($limit, $offset);
    }

    /**
     * @throws UnexpectedValueException
     */
    public function createQueue(QueueInterface $queueable): QueueEntityInterface
    {
        $queue = $this->entityFactory->createQueue($queueable);

        return $this->save($queue);
    }

    public function flush(QueueEntityInterface $entity = null): void
    {
        $this->repository->flush($entity);
    }

    public function save(QueueEntityInterface $entity): QueueEntityInterface
    {
        $this->repository->save($entity);

        return $entity;
    }

    public function isTransactionActive(): bool
    {
        return $this->repository->isTransactionActive();
    }

    public function beginTransaction(): void
    {
        $this->repository->beginTransaction();
    }

    public function rollback(): void
    {
        $this->repository->rollback();
    }

    public function commit(): void
    {
        $this->repository->commit();
    }
}
