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

/** @psalm-immutable */
class EndPoint
{
    private string $host;
    private int $port;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /** @psalm-pure */
    public function host(): string
    {
        return $this->host;
    }

    /** @psalm-pure */
    public function port(): int
    {
        return $this->port;
    }

    /** @psalm-pure */
    public function equals(EndPoint $endPoint): bool
    {
        return $this->host === $endPoint->host && $this->port === $endPoint->port;
    }

    /** @psalm-pure */
    public function __toString(): string
    {
        return $this->host . ':' . $this->port;
    }
}
