<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Entity;

use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PDO;

class QueueRepository extends EntityRepository
{
    /**
     * @param int      $maxAttempts
     * @param int      $limit
     * @param int|null $offset
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return array|QueueEntityMappedSuperclass[]
     */
    public function getToRestore(int $maxAttempts, int $limit, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('queue');
        $qb
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('queue.status', ':status_error'),
                        $qb->expr()->lt('queue.attempts', ':max_attempts'),
                        $qb->expr()->eq('queue.isDeleted', ':not_deleted')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->orX(
                            $qb->expr()->eq('queue.status', ':status_initial'),
                            $qb->expr()->eq('queue.status', ':status_new')
                        ),
                        $qb->expr()->eq('queue.isDeleted', ':not_deleted')
                    )
                )
            )
            ->setParameters(
                [
                    'status_error' => QueueEntityMappedSuperclass::STATUS_ERROR,
                    'status_new' => QueueEntityMappedSuperclass::STATUS_NEW,
                    'status_initial' => QueueEntityMappedSuperclass::STATUS_INITIAL,
                    'max_attempts' => $maxAttempts,
                ]
            )
            ->setParameter('not_deleted', false, PDO::PARAM_BOOL)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('queue.id', 'ASC');

        return $qb->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->execute();
    }

    /**
     * @param int      $limit
     * @param int|null $offset
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return array|QueueEntityMappedSuperclass[]
     */
    public function getToRepublish(int $limit, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('queue');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('queue.status', ':statuses'),
                    $qb->expr()->lte('queue.scheduledAt', ':now')
                )
            )
            ->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('queue.status', ':initial_status'),
                    $qb->expr()->lte('queue.createdAt', ':from_created_at')
                )
            )
            ->andWhere(
                $qb->expr()->eq('queue.isDeleted', ':not_deleted')
            )
            ->setParameters(
                [
                    'statuses' => [
                        QueueEntityMappedSuperclass::STATUS_WAITING,
                        QueueEntityMappedSuperclass::STATUS_SCHEDULED,
                    ],
                    'now' => new DateTime(),
                    'initial_status' => QueueEntityMappedSuperclass::STATUS_INITIAL,
                    'from_created_at' => (new DateTime())->modify('-5 minute'),
                ]
            )
            ->setParameter('not_deleted', false, PDO::PARAM_BOOL)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('queue.id', 'ASC');

        return $qb->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->execute();
    }

    public function getQueryBuilder(
        Expr\Base $filterExpression = null,
        Expr\OrderBy $sortExpression = null
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('queue')
            ->where('queue.isDeleted = false');
        if (null !== $filterExpression) {
            $queryBuilder->andWhere($filterExpression);
        }

        if (null !== $sortExpression) {
            $queryBuilder->orderBy($sortExpression);
        }

        return $queryBuilder;
    }

    public function save($entity): void
    {
        $this->persist($entity);
        $this->flush($entity);
    }

    public function persist($entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    public function flush($entity = null): void
    {
        $this->getEntityManager()->flush($entity);
    }

    public function beginTransaction(): void
    {
        $this->getEntityManager()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getEntityManager()->commit();
    }

    public function rollback(): void
    {
        $this->getEntityManager()->rollback();
    }

    public function isTransactionActive(): bool
    {
        return $this->getEntityManager()->getConnection()->isTransactionActive();
    }
}
