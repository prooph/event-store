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

namespace Prooph\EventStore;

class ClientConnectionEventArgs implements EventArgs
{
    private EventStoreConnection $connection;
    private EndPoint $remoteEndPoint;

    public function __construct(EventStoreConnection $connection, EndPoint $remoteEndPoint)
    {
        $this->connection = $connection;
        $this->remoteEndPoint = $remoteEndPoint;
    }

    public function connection(): EventStoreConnection
    {
        return $this->connection;
    }

    public function remoteEndPoint(): EndPoint
    {
        return $this->remoteEndPoint;
    }
}
