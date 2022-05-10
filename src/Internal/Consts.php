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

namespace Prooph\EventStore\Internal;

/** @internal */
class Consts
{
    public const DefaultMaxQueueSize = 5000;

    public const DefaultMaxConcurrentItems = 5000;

    public const DefaultMaxOperationsRetry = 10;

    public const DefaultMaxReconnections = 10;

    public const DefaultRequireMaster = true;

    public const DefaultReconnectionDelay = 0.1; // seconds

    public const DefaultOperationTimeout = 7; // seconds

    public const DefaultOperationTimeoutPeriod = 1; // seconds

    public const TimerPeriod = 0.2; // seconds

    public const MaxReadSize = 4096;

    public const DefaultMaxClusterDiscoverAttempts = 10;

    public const DefaultClusterManagerExternalHttpPort = 30778;

    public const CatchUpDefaultReadBatchSize = 500;

    public const CatchUpDefaultMaxPushQueueSize = 10000;
}
