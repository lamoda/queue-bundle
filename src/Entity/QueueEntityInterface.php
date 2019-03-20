<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Entity;

use DateTime;

interface QueueEntityInterface
{
    public function getId(): int;

    public function getName(): string;

    public function getExchange(): string;

    public function getJobName(): string;

    public function getData(): ?array;

    public function getStatus(): int;

    public function getStatusAsString(): string;

    public function incAttempts(): self;

    public function getAttempts(): int;

    public function isMaxAttemptsReached(int $maxAttempts): bool;

    public function setExecuted(DateTime $executed): self;

    public function getExecuted(): ?DateTime;

    public function getFinishedAt(): ?DateTime;

    public function setFinishedAt(DateTime $finishedAt): self;

    public function setError(): self;

    public function setNew(): self;

    public function setAttemptsReached(): self;

    public function setDone(): self;

    public function setScheduled(DateTime $scheduledAt): self;

    public function setWaiting(DateTime $waitTil): self;

    public function setInProgress(): self;

    public function reset(): self;

    public function setIsDeleted(bool $isDeleted): self;

    public function getIsDeleted(): bool;

    public function setCreatedAt(DateTime $createdAt): self;

    public function getCreatedAt(): DateTime;

    public function setUpdatedAt(DateTime $updatedAt): self;

    public function getUpdatedAt(): DateTime;

    public function setScheduledAt(?DateTime $scheduledAt): self;

    public function getScheduledAt(): ?DateTime;

    public function isScheduled(): bool;
}
