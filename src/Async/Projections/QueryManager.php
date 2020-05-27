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

namespace Prooph\EventStore\Async\Projections;

use Amp\Promise;
use Prooph\EventStore\Projections\State;
use Prooph\EventStore\UserCredentials;

interface QueryManager
{
    /**
     * Asynchronously executes a query
     *
     * Creates a new transient projection and polls its status until it is Completed
     *
     * @return Promise<State>
     */
    public function executeAsync(
        string $name,
        string $query,
        int $initialPollingDelay,
        int $maximumPollingDelay,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;
}
