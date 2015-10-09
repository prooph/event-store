<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/09/15 - 07:20 PM
 */

namespace Prooph\EventStore\Snapshot;

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
    public function __construct(AggregateType $aggregateType, $aggregateId, $aggregateRoot, $lastVersion, $createdAt)
    {
        $this->aggregateType = $aggregateType;
        $this->aggregateId = $aggregateId;
        $this->aggregateRoot = $aggregateRoot;
        $this->lastVersion = $lastVersion;
        $this->createdAt = $createdAt;
    }

    /**
     * @return AggregateType
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return string
     */
    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    /**
     * @return object
     */
    public function getAggregateRoot()
    {
        return $this->aggregateRoot;
    }

    /**
     * @return int
     */
    public function getLastVersion()
    {
        return $this->lastVersion;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
