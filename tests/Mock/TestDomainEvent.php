<?php

/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

class TestDomainEvent extends DomainEvent implements PayloadConstructable
{
    use PayloadTrait;

    public static function with(array $payload, int $version): TestDomainEvent
    {
        $event = new static($payload);

        return $event->withVersion($version);
    }

    public static function withPayloadAndSpecifiedCreatedAt(array $payload, int $version, \DateTimeImmutable $createdAt): TestDomainEvent
    {
        $event = new static($payload);
        $event->createdAt = $createdAt;

        return $event->withVersion($version);
    }

    public function withVersion(int $version): TestDomainEvent
    {
        return $this->withAddedMetadata('_aggregate_version', $version);
    }

    public function version(): int
    {
        return $this->metadata['_aggregate_version'];
    }
}
