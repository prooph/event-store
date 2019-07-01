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

namespace Prooph\EventStore\Internal;

/** @internal */
class Consts
{
    public const DEFAULT_MAX_QUEUE_SIZE = 5000;
    public const DEFAULT_MAX_CONCURRENT_ITEMS = 5000;
    public const DEFAULT_MAX_OPERATIONS_RETRY = 10;
    public const DEFAULT_MAX_RECONNECTIONS = 10;

    public const DEFAULT_REQUIRE_MASTER = true;

    public const DEFAULT_RECONNECTION_DELAY = 100; // milliseconds
    public const DEFAULT_OPERATION_TIMEOUT = 7000; // milliseconds
    public const DEFAULT_OPERATION_TIMEOUT_CHECK_PERIOD = 1000; // milliseconds

    public const TIMER_PERIOD = 200; // milliseconds
    public const MAX_READ_SIZE = 4096;
    public const DEFAULT_MAX_CLUSTER_DISCOVER_ATTEMPTS = 10;
    public const DEFAULT_CLUSTER_MANAGER_EXTERNAL_HTTP_PORT = 30778;

    public const CATCH_UP_DEFAULT_READ_BATCH_SIZE = 500;
    public const CATCH_UP_DEFAULT_MAX_PUSH_QUEUE_SIZE = 10000;
}
