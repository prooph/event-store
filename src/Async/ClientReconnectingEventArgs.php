<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async;

use Prooph\EventStore\EventArgs;

class ClientReconnectingEventArgs implements EventArgs
{
    /** @var EventStoreConnection */
    private $connection;

    public function __construct(EventStoreConnection $connection)
    {
        $this->connection = $connection;
    }

    public function connection(): EventStoreConnection
    {
        return $this->connection;
    }
}
