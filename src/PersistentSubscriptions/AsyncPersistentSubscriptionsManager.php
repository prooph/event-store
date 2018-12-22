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

namespace Prooph\EventStore\PersistentSubscriptions;

use Amp\Promise;
use Prooph\EventStore\UserCredentials;

interface AsyncPersistentSubscriptionsManager
{
    /**
     * @param string $stream
     * @param string $subscriptionName
     * @param null|UserCredentials $userCredentials
     * @return Promise<PersistentSubscriptionDetails>
     */
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

    /**
     * @param null|string $stream
     * @param null|UserCredentials $userCredentials
     * @return Promise<PersistentSubscriptionDetails[]>
     */
    public function list(?string $stream = null, ?UserCredentials $userCredentials = null): Promise;
}
