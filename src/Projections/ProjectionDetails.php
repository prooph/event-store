<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

/** @psalm-immutable */
final class ProjectionDetails
{
    private int $coreProcessingTime;

    private int $version;

    private int $epoch;

    private string $effectiveName;

    private int $writesInProgress;

    private int $readsInProgress;

    private int $partitionsCached;

    private string $status;

    private ?string $stateReason;

    private string $name;

    private string $mode;

    private string $position;

    private float $progress;

    private ?string $lastCheckpoint;

    private int $eventsProcessedAfterRestart;

    private string $statusUrl;

    private string $stateUrl;

    private string $resultUrl;

    private string $queryUrl;

    private string $enableCommandUrl;

    private string $disableCommandUrl;

    private ?string $checkpointStatus;

    private int $bufferedEvents;

    private int $writePendingEventsBeforeCheckpoint;

    private int $writePendingEventsAfterCheckpoint;

    public function __construct(
        int $coreProcessingTime,
        int $version,
        int $epoch,
        string $effectiveName,
        int $writesInProgress,
        int $readsInProgress,
        int $partitionsCached,
        string $status,
        ?string $stateReason,
        string $name,
        string $mode,
        string $position,
        float $progress,
        ?string $lastCheckpoint,
        int $eventsProcessedAfterRestart,
        string $statusUrl,
        string $stateUrl,
        string $resultUrl,
        string $queryUrl,
        string $enableCommandUrl,
        string $disableCommandUrl,
        ?string $checkpointStatus,
        int $bufferedEvents,
        int $writePendingEventsBeforeCheckpoint,
        int $writePendingEventsAfterCheckpoint
    ) {
        $this->coreProcessingTime = $coreProcessingTime;
        $this->version = $version;
        $this->epoch = $epoch;
        $this->effectiveName = $effectiveName;
        $this->writesInProgress = $writesInProgress;
        $this->readsInProgress = $readsInProgress;
        $this->partitionsCached = $partitionsCached;
        $this->status = $status;
        $this->stateReason = $stateReason;
        $this->name = $name;
        $this->mode = $mode;
        $this->position = $position;
        $this->progress = $progress;
        $this->lastCheckpoint = $lastCheckpoint;
        $this->eventsProcessedAfterRestart = $eventsProcessedAfterRestart;
        $this->statusUrl = $statusUrl;
        $this->stateUrl = $stateUrl;
        $this->resultUrl = $resultUrl;
        $this->queryUrl = $queryUrl;
        $this->enableCommandUrl = $enableCommandUrl;
        $this->disableCommandUrl = $disableCommandUrl;
        $this->checkpointStatus = $checkpointStatus;
        $this->bufferedEvents = $bufferedEvents;
        $this->writePendingEventsBeforeCheckpoint = $writePendingEventsBeforeCheckpoint;
        $this->writePendingEventsAfterCheckpoint = $writePendingEventsAfterCheckpoint;
    }

    /** @psalm-mutation-free */
    public function coreProcessingTime(): int
    {
        return $this->coreProcessingTime;
    }

    /** @psalm-mutation-free */
    public function version(): int
    {
        return $this->version;
    }

    /** @psalm-mutation-free */
    public function epoch(): int
    {
        return $this->epoch;
    }

    /** @psalm-mutation-free */
    public function effectiveName(): string
    {
        return $this->effectiveName;
    }

    /** @psalm-mutation-free */
    public function writesInProgress(): int
    {
        return $this->writesInProgress;
    }

    /** @psalm-mutation-free */
    public function readsInProgress(): int
    {
        return $this->readsInProgress;
    }

    /** @psalm-mutation-free */
    public function partitionsCached(): int
    {
        return $this->partitionsCached;
    }

    /** @psalm-mutation-free */
    public function status(): string
    {
        return $this->status;
    }

    /** @psalm-mutation-free */
    public function stateReason(): ?string
    {
        return $this->stateReason;
    }

    /** @psalm-mutation-free */
    public function name(): string
    {
        return $this->name;
    }

    /** @psalm-mutation-free */
    public function mode(): string
    {
        return $this->mode;
    }

    /** @psalm-mutation-free */
    public function position(): string
    {
        return $this->position;
    }

    /** @psalm-mutation-free */
    public function progress(): float
    {
        return $this->progress;
    }

    /** @psalm-mutation-free */
    public function lastCheckpoint(): ?string
    {
        return $this->lastCheckpoint;
    }

    /** @psalm-mutation-free */
    public function eventsProcessedAfterRestart(): int
    {
        return $this->eventsProcessedAfterRestart;
    }

    /** @psalm-mutation-free */
    public function statusUrl(): string
    {
        return $this->statusUrl;
    }

    /** @psalm-mutation-free */
    public function stateUrl(): string
    {
        return $this->stateUrl;
    }

    /** @psalm-mutation-free */
    public function resultUrl(): string
    {
        return $this->resultUrl;
    }

    /** @psalm-mutation-free */
    public function queryUrl(): string
    {
        return $this->queryUrl;
    }

    /** @psalm-mutation-free */
    public function enableCommandUrl(): string
    {
        return $this->enableCommandUrl;
    }

    /** @psalm-mutation-free */
    public function disableCommandUrl(): string
    {
        return $this->disableCommandUrl;
    }

    /** @psalm-mutation-free */
    public function checkpointStatus(): ?string
    {
        return $this->checkpointStatus;
    }

    /** @psalm-mutation-free */
    public function bufferedEvents(): int
    {
        return $this->bufferedEvents;
    }

    /** @psalm-mutation-free */
    public function writePendingEventsBeforeCheckpoint(): int
    {
        return $this->writePendingEventsBeforeCheckpoint;
    }

    /** @psalm-mutation-free */
    public function writePendingEventsAfterCheckpoint(): int
    {
        return $this->writePendingEventsAfterCheckpoint;
    }
}
