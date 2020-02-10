<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

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

    public function coreProcessingTime(): int
    {
        return $this->coreProcessingTime;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function epoch(): int
    {
        return $this->epoch;
    }

    public function effectiveName(): string
    {
        return $this->effectiveName;
    }

    public function writesInProgress(): int
    {
        return $this->writesInProgress;
    }

    public function readsInProgress(): int
    {
        return $this->readsInProgress;
    }

    public function partitionsCached(): int
    {
        return $this->partitionsCached;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function stateReason(): ?string
    {
        return $this->stateReason;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function position(): string
    {
        return $this->position;
    }

    public function progress(): float
    {
        return $this->progress;
    }

    public function lastCheckpoint(): ?string
    {
        return $this->lastCheckpoint;
    }

    public function eventsProcessedAfterRestart(): int
    {
        return $this->eventsProcessedAfterRestart;
    }

    public function statusUrl(): string
    {
        return $this->statusUrl;
    }

    public function stateUrl(): string
    {
        return $this->stateUrl;
    }

    public function resultUrl(): string
    {
        return $this->resultUrl;
    }

    public function queryUrl(): string
    {
        return $this->queryUrl;
    }

    public function enableCommandUrl(): string
    {
        return $this->enableCommandUrl;
    }

    public function disableCommandUrl(): string
    {
        return $this->disableCommandUrl;
    }

    public function checkpointStatus(): ?string
    {
        return $this->checkpointStatus;
    }

    public function bufferedEvents(): int
    {
        return $this->bufferedEvents;
    }

    public function writePendingEventsBeforeCheckpoint(): int
    {
        return $this->writePendingEventsBeforeCheckpoint;
    }

    public function writePendingEventsAfterCheckpoint(): int
    {
        return $this->writePendingEventsAfterCheckpoint;
    }
}
