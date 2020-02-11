<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Entity;

use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;
use Lamoda\QueueBundle\Tests\Unit\QueueEntity;
use Lamoda\QueueBundle\Tests\Unit\Reflection;
use PHPUnit_Framework_TestCase;

class QueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataQueues
     */
    public function testStatusAsString(string $statusTitle, QueueEntityInterface $queue): void
    {
        $this->assertEquals($statusTitle, $queue->getStatusAsString());
    }

    /**
     * @throws \Exception
     */
    public function dataQueues(): array
    {
        $queueInitial = $this->createQueue();

        $queueNew = clone $queueInitial;
        $queueNew->setNew();

        $queueInProgress = clone $queueInitial;
        $queueInProgress->setInProgress();

        $queueDone = clone $queueInProgress;
        $queueDone->setDone();

        $queueError = clone $queueDone;
        $queueError->setError();

        $queueAttemptsReached = clone $queueError;
        $queueAttemptsReached->setAttemptsReached();

        $queueScheduled = clone $queueInitial;
        $queueScheduled->setScheduled(new \DateTime('+1 day'));

        $queueNotFound = clone $queueInitial;
        Reflection::setProtectedProperty($queueNotFound, 'status', 32767); //max postgres smallint

        return [
            [
                QueueEntityMappedSuperclass::STATUS_INITIAL_TITLE,
                $queueInitial,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_NEW_TITLE,
                $queueNew,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_IN_PROGRESS_TITLE,
                $queueInProgress,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_DONE_TITLE,
                $queueDone,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_ERROR_TITLE,
                $queueError,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_ATTEMPTS_REACHED_TITLE,
                $queueAttemptsReached,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_SCHEDULED_TITLE,
                $queueScheduled,
            ],
            [
                QueueEntityMappedSuperclass::STATUS_NOT_FOUND_TITLE,
                $queueNotFound,
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testInvalidStatusAsString(): void
    {
        $queue = $this->createQueue();
        Reflection::callProtectedMethod($queue, 'setStatus', 999);

        $this->assertEquals(QueueEntityMappedSuperclass::STATUS_NOT_FOUND_TITLE, $queue->getStatusAsString());
    }

    public function dataMaxAttemptsReached(): array
    {
        return [
            [
                4,
                false,
            ],
            [
                3,
                true,
            ],
            [
                1,
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataMaxAttemptsReached()
     */
    public function testMaxAttemptsReached(int $maxAttempts, bool $expected): void
    {
        $queue = $this->createQueue();
        $queue->incAttempts()
            ->incAttempts()
            ->incAttempts();

        $this->assertEquals($expected, $queue->isMaxAttemptsReached($maxAttempts));
    }

    public function testReset(): void
    {
        $initQueue = $this->createQueue();

        $resetQueue = clone $initQueue;
        $resetQueue->incAttempts()
            ->setDone();

        $doneQueue = clone $resetQueue;

        $resetQueue->reset();

        $this->assertNotEquals($resetQueue, $doneQueue);
        $this->assertEquals($initQueue, $resetQueue);
    }

    private function createQueue(): QueueEntity
    {
        return new QueueEntity('queue', 'exchange', 'ClassJob', []);
    }
}
