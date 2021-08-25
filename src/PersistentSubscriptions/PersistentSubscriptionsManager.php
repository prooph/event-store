<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\PersistentSubscriptions;

use Prooph\EventStore\UserCredentials;

interface PersistentSubscriptionsManager
{
    public function describe(
        string $stream,
        string $subscriptionName,
        ?UserCredentials $userCredentials = null
    ): PersistentSubscriptionDetails;

    public function replayParkedMessages(
        string $stream,
        string $subscriptionName,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * @return list<PersistentSubscriptionDetails>
     */
    public function list(?string $stream = null, ?UserCredentials $userCredentials = null): array;
}
