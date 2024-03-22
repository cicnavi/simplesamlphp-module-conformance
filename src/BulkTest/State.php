<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\BulkTest;

use DateInterval;
use DateTimeImmutable;

class State
{
    final public const MAX_NUMBER_OF_MESSAGES_TO_KEEP = 100;
    final public const DEFAULT_NUMBER_OF_MESSAGES_TO_KEEP = 10;
    protected DateTimeImmutable $updatedAt;
    protected ?DateTimeImmutable $endedAt = null;
    protected int $successfulJobsProcessed = 0;
    protected int $failedJobsProcessed = 0;
    /**
     * @var string[]
     */
    protected array $statusMessages = [];
    protected int $numberOfStatusMessagesToKeep = 10;
    protected bool $isGracefulInterruptInitiated = false;

    public function __construct(
        protected int $runnerId,
        protected ?DateTimeImmutable $startedAt = null,
        DateTimeImmutable $updatedAt = null,
        int $numberOfStatusMessagesToKeep = self::DEFAULT_NUMBER_OF_MESSAGES_TO_KEEP
    ) {
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();

        $this->numberOfStatusMessagesToKeep =
            $numberOfStatusMessagesToKeep > 0 && $numberOfStatusMessagesToKeep <= self::MAX_NUMBER_OF_MESSAGES_TO_KEEP ?
            $numberOfStatusMessagesToKeep :
            self::DEFAULT_NUMBER_OF_MESSAGES_TO_KEEP;
    }

    /**
     * @return int
     */
    public function getRunnerId(): int
    {
        return $this->runnerId;
    }

    /**
     * @return ?DateTimeImmutable
     */
    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Set startedAt if not already set.
     * @return bool True if set, false otherwise.
     */
    public function setStartedAt(DateTimeImmutable $startedAt): bool
    {
        if ($this->startedAt === null) {
            $this->startedAt = $startedAt;
            return true;
        }

        return false;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Set endedAt if not already set.
     * @return bool True if set, false otherwise.
     */
    public function setEndedAt(DateTimeImmutable $endedAt): bool
    {
        if ($this->endedAt === null) {
            $this->endedAt = $endedAt;
            return true;
        }

        return false;
    }

    /**
     * @return ?DateTimeImmutable
     */
    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function hasRunStarted(): bool
    {
        return $this->startedAt !== null;
    }

    public function incrementSuccessfulJobsProcessed(): void
    {
        $this->successfulJobsProcessed++;
    }

    public function incrementFailedJobsProcessed(): void
    {
        $this->failedJobsProcessed++;
    }

    /**
     * @return int
     */
    public function getSuccessfulJobsProcessed(): int
    {
        return $this->successfulJobsProcessed;
    }

    /**
     * @return int
     */
    public function getFailedJobsProcessed(): int
    {
        return $this->failedJobsProcessed;
    }

    public function isStale(DateInterval $threshold): bool
    {
        $minDateTime = (new DateTimeImmutable())->sub($threshold);

        if ($this->getUpdatedAt() < $minDateTime) {
            return true;
        }

        return false;
    }

    public function getTotalJobsProcessed(): int
    {
        return $this->getSuccessfulJobsProcessed() + $this->getFailedJobsProcessed();
    }

    public function addStatusMessage(string $message): void
    {
        $this->statusMessages[] = $message;

        if (count($this->statusMessages) > $this->numberOfStatusMessagesToKeep) {
            array_shift($this->statusMessages);
        }
    }

    /**
     * @return string[]
     */
    public function getStatusMessages(): array
    {
        return $this->statusMessages;
    }

    public function getLastStatusMessage(): ?string
    {
        if (empty($this->statusMessages)) {
            return null;
        }

        $message = end($this->statusMessages);
        reset($this->statusMessages);

        return $message;
    }

    /**
     * @return bool
     */
    public function getIsGracefulInterruptInitiated(): bool
    {
        return $this->isGracefulInterruptInitiated;
    }

    public function setIsGracefulInterruptInitiated(bool $isGracefulInterruptInitiated): void
    {
        $this->isGracefulInterruptInitiated = $isGracefulInterruptInitiated;
    }

    public function setNumberOfStatusMessagesToKeep(int $numberOfStatusMessagesToKeep): void
    {
        $this->numberOfStatusMessagesToKeep = $numberOfStatusMessagesToKeep;
    }
}