<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Exception;

class ConnectionClosed extends EventStoreConnectionException
{
    public static function withName(string $name): ConnectionClosed
    {
        return new self(\sprintf(
            'Connection \'%s\' was closed',
            $name
        ));
    }
}
