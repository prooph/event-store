<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 06/06/14 - 11:34 PM
 */

namespace Prooph\EventStore\Aggregate;

/**
 * Class AggregateType
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
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
     * @param mixed $eventSourcedAggregateRoot
     * @return AggregateType
     * @throws \InvalidArgumentException
     */
    public static function fromAggregateRoot($eventSourcedAggregateRoot)
    {
        if (! is_object($eventSourcedAggregateRoot)) {
            throw new \InvalidArgumentException(
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
     * @param string $aggregateRootClass
     * @return AggregateType
     * @throws \InvalidArgumentException
     */
    public static function fromAggregateRootClass($aggregateRootClass)
    {
        if (! is_string($aggregateRootClass)) {
            throw new \InvalidArgumentException('Aggregate root class must be a string');
        }
        if (! class_exists($aggregateRootClass)) {
            throw new \InvalidArgumentException(sprintf('Aggregate root class %s can not be found', $aggregateRootClass));
        }

        return new static($aggregateRootClass);
    }

    /**
     * Use this factory when the aggregate type is not equal to the aggregate root class
     *
     * @param $aggregateTypeString
     * @return AggregateType
     */
    public static function fromString($aggregateTypeString)
    {
        return new static($aggregateTypeString);
    }

    /**
     * @param $aggregateType
     * @throws \InvalidArgumentException
     */
    private function __construct($aggregateType)
    {
        if (! is_string($aggregateType) || empty($aggregateType)) {
            throw new \InvalidArgumentException('AggregateType must be a non empty string');
        }

        $this->aggregateType = $aggregateType;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->aggregateType;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param AggregateType $other
     * @return bool
     */
    public function equals(AggregateType $other)
    {
        return $this->toString() === $other->toString();
    }
}
