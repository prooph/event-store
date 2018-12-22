<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

interface EventStoreSubscription
{
    public function isSubscribedToAll(): bool;

    public function streamId(): string;

    public function lastCommitPosition(): int;

    public function lastEventNumber(): ?int;

    public function close(): void;

    public function unsubscribe(): void;
}
