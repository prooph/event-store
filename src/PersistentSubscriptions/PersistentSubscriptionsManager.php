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
     * @param null|string $stream
     * @param null|UserCredentials $userCredentials
     * @return PersistentSubscriptionDetails[]
     */
    public function list(?string $stream = null, ?UserCredentials $userCredentials = null): array;
}
