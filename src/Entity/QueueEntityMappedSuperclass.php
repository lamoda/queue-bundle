<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks()
 */
abstract class QueueEntityMappedSuperclass implements QueueEntityInterface
{
    public const STATUS_INITIAL_TITLE = 'initial';
    public const STATUS_NEW_TITLE = 'new';
    public const STATUS_IN_PROGRESS_TITLE = 'in progress';
    public const STATUS_DONE_TITLE = 'done';
    public const STATUS_ERROR_TITLE = 'error';
    public const STATUS_ATTEMPTS_REACHED_TITLE = 'max attempts reached';
    public const STATUS_WAITING_TITLE = 'waiting';
    public const STATUS_SCHEDULED_TITLE = 'scheduled';

    public const STATUS_INITIAL = 0;
    public const STATUS_NEW = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_DONE = 3;
    public const STATUS_ERROR = 4;
    public const STATUS_ATTEMPTS_REACHED = 5;
    public const STATUS_WAITING = 6;
    public const STATUS_SCHEDULED = 7;

    public const STATUS_NOT_FOUND_TITLE = 'not found';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\SequenceGenerator(sequenceName="queue_id_seq", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="exchange", type="string", length=64, nullable=false)
     */
    protected $exchange;

    /**
     * @var string
     *
     * @ORM\Column(name="job_name", type="string", length=64, nullable=false)
     */
    protected $jobName;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    protected $status = self::STATUS_INITIAL;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", name="attempts", options={"default": 0})
     */
    protected $attempts = 0;

    /**
     * @var DateTime | null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $executed;

    /**
     * @var DateTime | null
     *
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    protected $finishedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="created_at", nullable=false)
     */
    protected $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="updated_at", nullable=false)
     */
    protected $updatedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", name="scheduled_at", nullable=true)
     */
    protected $scheduledAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_deleted", type="boolean", options={"default": 0}))
     */
    protected $isDeleted = false;

    private static $statusMapping = [
        self::STATUS_INITIAL => self::STATUS_INITIAL_TITLE,
        self::STATUS_NEW => self::STATUS_NEW_TITLE,
        self::STATUS_IN_PROGRESS => self::STATUS_IN_PROGRESS_TITLE,
        self::STATUS_DONE => self::STATUS_DONE_TITLE,
        self::STATUS_ERROR => self::STATUS_ERROR_TITLE,
        self::STATUS_ATTEMPTS_REACHED => self::STATUS_ATTEMPTS_REACHED_TITLE,
        self::STATUS_WAITING => self::STATUS_WAITING_TITLE,
        self::STATUS_SCHEDULED => self::STATUS_SCHEDULED_TITLE,
    ];

    public function __construct(string $name, string $exchange, string $jobName, array $data)
    {
        $this->name = $name;
        $this->exchange = $exchange;
        $this->jobName = $jobName;
        $this->data = $data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function setStatus(int $status): QueueEntityInterface
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusAsString(): string
    {
        if (isset(self::$statusMapping[$this->getStatus()])) {
            return self::$statusMapping[$this->getStatus()];
        }

        return self::STATUS_NOT_FOUND_TITLE;
    }

    public function incAttempts(): QueueEntityInterface
    {
        ++$this->attempts;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function isMaxAttemptsReached(int $maxAttempts): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    public function setExecuted(DateTime $executed): QueueEntityInterface
    {
        $this->executed = $executed;

        return $this;
    }

    public function getExecuted(): ?DateTime
    {
        return $this->executed;
    }

    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(DateTime $finishedAt): QueueEntityInterface
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function setError(): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_ERROR)
            ->setScheduledAt(null);
    }

    public function setNew(): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_NEW)
            ->setScheduledAt(null);
    }

    public function setAttemptsReached(): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_ATTEMPTS_REACHED)
            ->setScheduledAt(null);
    }

    public function setDone(): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_DONE)
            ->setFinishedAt(new DateTime())
            ->setScheduledAt(null);
    }

    public function setScheduled(DateTime $scheduledAt): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_SCHEDULED)
            ->setScheduledAt($scheduledAt);
    }

    public function setWaiting(DateTime $waitTil): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_WAITING)
            ->setScheduledAt($waitTil);
    }

    public function setInProgress(): QueueEntityInterface
    {
        return $this->setStatus(self::STATUS_IN_PROGRESS)
            ->setExecuted(new DateTime())
            ->setScheduledAt(null)
            ->incAttempts();
    }

    public function reset(): QueueEntityInterface
    {
        $this->attempts = 0;
        $this->status = self::STATUS_INITIAL;
        $this->executed = null;
        $this->finishedAt = null;
        $this->scheduledAt = null;

        return $this;
    }

    public function setIsDeleted(bool $isDeleted): QueueEntityInterface
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setCreatedAt(DateTime $createdAt): QueueEntityInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): QueueEntityInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setScheduledAt(?DateTime $scheduledAt): QueueEntityInterface
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getScheduledAt(): ?DateTime
    {
        return $this->scheduledAt;
    }

    public function isScheduled(): bool
    {
        return $this->status === static::STATUS_SCHEDULED;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->setCreatedAt(new DateTime());
        $this->setUpdatedAt(new DateTime());
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->setUpdatedAt(new DateTime());
    }
}
