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

use Prooph\EventStore\EventArgs;

class ClientClosedEventArgs implements EventArgs
{
    private EventStoreConnection $connection;
    private string $reason;

    public function __construct(EventStoreConnection $connection, string $reason)
    {
        $this->connection = $connection;
        $this->reason = $reason;
    }

    public function connection(): EventStoreConnection
    {
        return $this->connection;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
