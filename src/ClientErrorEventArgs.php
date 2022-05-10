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

namespace Prooph\EventStore;

use Throwable;

class ClientErrorEventArgs implements EventArgs
{
    private EventStoreConnection $connection;

    private Throwable $exception;

    public function __construct(EventStoreConnection $connection, Throwable $exception)
    {
        $this->connection = $connection;
        $this->exception = $exception;
    }

    public function connection(): EventStoreConnection
    {
        return $this->connection;
    }

    public function exception(): Throwable
    {
        return $this->exception;
    }
}
