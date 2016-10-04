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

namespace Prooph\EventStore\Aggregate;

/**
 * Class AggregateType
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <contact@prooph.de>
 */
class AggregateType
{
    /**
     * @var string
     */
    protected $aggregateType;

    /**
     * Use this factory when aggregate type should be detected based on given aggregate root
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromAggregateRoot(object $eventSourcedAggregateRoot) : AggregateType
    {
        if (! is_object($eventSourcedAggregateRoot)) {
            throw new Exception\AggregateTypeException(
                sprintf('Aggregate root must be an object but type of %s given', gettype($eventSourcedAggregateRoot))
            );
        }

        if ($eventSourcedAggregateRoot instanceof AggregateTypeProvider) {
            return $eventSourcedAggregateRoot->aggregateType();
        }

        return new static(get_class($eventSourcedAggregateRoot));
    }

    /**
     * Use this factory when aggregate type equals to aggregate root class
     * The factory makes sure that the aggregate root class exists.
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromAggregateRootClass(string $aggregateRootClass) : AggregateType
    {
        if (! is_string($aggregateRootClass)) {
            throw new Exception\InvalidArgumentException('Aggregate root class must be a string');
        }
        if (! class_exists($aggregateRootClass)) {
            throw new Exception\InvalidArgumentException(sprintf('Aggregate root class %s can not be found', $aggregateRootClass));
        }

        return new static($aggregateRootClass);
    }

    /**
     * Use this factory when the aggregate type is not equal to the aggregate root class
     */
    public static function fromString(string $aggregateTypeString) : AggregateType
    {
        return new static($aggregateTypeString);
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    private function __construct(string $aggregateType)
    {
        if (empty($aggregateType)) {
            throw new Exception\InvalidArgumentException('AggregateType must be a non empty string');
        }

        $this->aggregateType = $aggregateType;
    }

    public function toString() : string
    {
        return $this->aggregateType;
    }

    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * @throws Exception\AggregateTypeException
     */
    public function assert(object $aggregateRoot)
    {
        $otherAggregateType = self::fromAggregateRoot($aggregateRoot);

        if (! $this->equals($otherAggregateType)) {
            throw new Exception\AggregateTypeException(
                sprintf('Aggregate types must be equal. %s != %s', $this->toString(), $otherAggregateType->toString())
            );
        }
    }

    public function equals(AggregateType $other) : bool
    {
        return $this->toString() === $other->toString();
    }
}
