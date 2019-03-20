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
     * @param int      $limit
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
     * @param int $id
     *
     * @throws Exception
     *
     * @return QueueEntityInterface
     */
    public function getToProcess(int $id): QueueEntityInterface
    {
        $this->repository->beginTransaction();

        try {
            $queueEntity = $this->repository->findOneBy(
                [
                    'id' => $id,
                ]
            );

            if (!($queueEntity instanceof QueueEntityInterface)) {
                throw new UnexpectedValueException(sprintf(ConstantMessage::QUEUE_ENTITY_NOT_FOUND, $id));
            }

            if (QueueEntityMappedSuperclass::STATUS_NEW !== $queueEntity->getStatus()) {
                throw new UnexpectedValueException(sprintf(
                    ConstantMessage::QUEUE_ENTITY_NOT_FOUND_IN_STATUS_NEW,
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
            $this->repository->commit();
        } catch (Exception $exception) {
            $this->repository->rollback();

            throw $exception;
        }

        if ($attemptsReached) {
            $this->eventDispatcher->dispatch(QueueAttemptsReachedEvent::NAME, new QueueAttemptsReachedEvent($queueEntity));
            throw new AttemptsReachedException(sprintf(ConstantMessage::QUEUE_ATTEMPTS_REACHED, $queueEntity->getName()));
        }

        return $queueEntity;
    }

    /**
     * @param int      $limit
     * @param int|null $offset
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return array|QueueEntityInterface[]
     */
    public function getToRepublish(int $limit, ?int $offset = null): array
    {
        return $this->repository->getToRepublish($limit, $offset);
    }

    public function createQueue(QueueInterface $queueable): QueueEntityInterface
    {
        return $this->save($this->entityFactory->createQueue($queueable));
    }

    public function flush(QueueEntityInterface $entity): QueueEntityInterface
    {
        $this->repository->flush($entity);

        return $entity;
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
