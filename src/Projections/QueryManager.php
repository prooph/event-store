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

namespace Prooph\EventStore\Projections;

use Prooph\EventStore\UserCredentials;

interface QueryManager
{
    /**
     * Synchronously executes a query
     *
     * Creates a new transient projection and polls its status until it is Completed
     *
     * returns String of JSON containing query result
     *
     * @param string $name A name for the query
     * @param string $query The source code for the query
     * @param int $initialPollingDelay Initial time to wait between polling for projection status
     * @param int $maximumPollingDelay Maximum time to wait between polling for projection status
     * @param string $type The type to use, defaults to JS
     * @param UserCredentials|null $userCredentials Credentials for a user with permission to create a query
     *
     * @return string
     */
    public function execute(
        string $name,
        string $query,
        int $initialPollingDelay,
        int $maximumPollingDelay,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): string;
}
