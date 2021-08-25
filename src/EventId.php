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

namespace Prooph\EventStore;

use Prooph\EventStore\Util\Guid;
use Ramsey\Uuid\UuidInterface;

/** @psalm-immutable */
class EventId
{
    private UuidInterface $uuid;

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
        /** @psalm-suppress ImpurePropertyAssignment */
        $this->uuid = $eventId;
    }

    /** @psalm-pure */
    public function toString(): string
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->uuid->toString();
    }

    /** @psalm-pure */
    public function toBinary(): string
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->uuid->getBytes();
    }

    /** @psalm-pure */
    public function __toString(): string
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->uuid->toString();
    }

    /** @psalm-pure */
    public function equals(EventId $other): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->uuid->equals($other->uuid);
    }
}
