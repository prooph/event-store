<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async;

use Amp\Promise;
use Prooph\EventStore\SubscriptionDropReason;
use Throwable;

interface EventStoreCatchUpSubscription
{
    public function isSubscribedToAll(): bool;

    public function streamId(): string;

    public function subscriptionName(): string;

    /** @internal */
    public function startAsync(): Promise;

    public function stop(?int $timeout = null): Promise;

    public function dropSubscription(SubscriptionDropReason $reason, ?Throwable $error): void;
}
