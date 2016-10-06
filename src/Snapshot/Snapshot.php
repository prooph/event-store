<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Snapshot;

use Assert\Assertion;
use DateTimeImmutable;
use Prooph\EventStore\Aggregate\AggregateType;

/**
 * Class Snapshot
 * @package Prooph\EventStore\Snapshot
 */
final class Snapshot
{
    /**
     * @var AggregateType
     */
    private $aggregateType;

    /**
     * @var string
     */
    private $aggregateId;

    /**
     * @var object
     */
    private $aggregateRoot;

    /**
     * @var int
     */
    private $lastVersion;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param object $aggregateRoot
     * @param int $lastVersion
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        AggregateType $aggregateType,
        string $aggregateId,
        $aggregateRoot,
        int $lastVersion,
        DateTimeImmutable $createdAt
    ) {
        Assertion::minLength($aggregateId, 1);
        Assertion::isObject($aggregateRoot);
        Assertion::min($lastVersion, 1);

        $this->aggregateType = $aggregateType;
        $this->aggregateId = $aggregateId;
        $this->aggregateRoot = $aggregateRoot;
        $this->lastVersion = $lastVersion;
        $this->createdAt = $createdAt;
    }

    public function aggregateType(): AggregateType
    {
        return $this->aggregateType;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    /**
     * @return object
     */
    public function aggregateRoot()
    {
        return $this->aggregateRoot;
    }

    public function lastVersion(): int
    {
        return $this->lastVersion;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
