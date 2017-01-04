<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $aggregateId,
        $aggregateRoot,
        $lastVersion,
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

    /**
     * @return AggregateType
     */
    public function aggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return string
     */
    public function aggregateId()
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

    /**
     * @return int
     */
    public function lastVersion()
    {
        return $this->lastVersion;
    }

    /**
     * @return DateTimeImmutable
     */
    public function createdAt()
    {
        return $this->createdAt;
    }
}
