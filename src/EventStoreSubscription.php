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

namespace Prooph\EventStore;

abstract class EventStoreSubscription
{
    private bool $isSubscribedToAll;
    private string $streamId;
    private int $lastCommitPosition;
    private ?int $lastEventNumber;

    public function __construct(string $streamId, int $lastCommitPosition, ?int $lastEventNumber)
    {
        $this->isSubscribedToAll = $streamId === '';
        $this->streamId = $streamId;
        $this->lastCommitPosition = $lastCommitPosition;
        $this->lastEventNumber = $lastEventNumber;
    }

    public function isSubscribedToAll(): bool
    {
        return $this->isSubscribedToAll;
    }

    public function streamId(): string
    {
        return $this->streamId;
    }

    public function lastCommitPosition(): int
    {
        return $this->lastCommitPosition;
    }

    public function lastEventNumber(): ?int
    {
        return $this->lastEventNumber;
    }

    public function __destruct()
    {
        $this->unsubscribe();
    }

    public function close(): void
    {
        $this->unsubscribe();
    }

    abstract public function unsubscribe(): void;
}
