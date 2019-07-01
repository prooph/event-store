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

namespace Prooph\EventStore;

use Prooph\EventStore\Util\Guid;
use Ramsey\Uuid\UuidInterface;

class EventId
{
    /** @var UuidInterface */
    private $uuid;

    public static function generate(): EventId
    {
        return new self(Guid::generate());
    }

    public static function fromString(string $eventId): EventId
    {
        return new self(Guid::fromString($eventId));
    }

    public static function fromBinary(string $bytes): EventId
    {
        return new self(Guid::fromBytes($bytes));
    }

    private function __construct(UuidInterface $eventId)
    {
        $this->uuid = $eventId;
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function toBinary(): string
    {
        return $this->uuid->getBytes();
    }

    public function __toString(): string
    {
        return $this->uuid->toString();
    }

    public function equals(EventId $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }
}
