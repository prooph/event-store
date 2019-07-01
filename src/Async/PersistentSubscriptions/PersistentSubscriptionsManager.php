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

namespace Prooph\EventStore\Async\PersistentSubscriptions;

use Amp\Promise;
use Prooph\EventStore\PersistentSubscriptions\PersistentSubscriptionDetails;
use Prooph\EventStore\UserCredentials;

interface PersistentSubscriptionsManager
{
    /** @return Promise<PersistentSubscriptionDetails> */
    public function describe(
        string $stream,
        string $subscriptionName,
        ?UserCredentials $userCredentials = null
    ): Promise;

    public function replayParkedMessages(
        string $stream,
        string $subscriptionName,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<PersistentSubscriptionDetails[]> */
    public function list(?string $stream = null, ?UserCredentials $userCredentials = null): Promise;
}
