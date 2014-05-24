<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 20:24
 */

namespace Prooph\EventStore\Mapping;

use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Prooph\EventStore\LifeCycleEvent\GetIdentifierProperty;

/**
 * Class AggregateRootDecorator
 *
 * @package Prooph\EventStore\Mapping
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateRootDecorator extends EventSourcedAggregateRoot
{
    /**
     * @param EventSourcedAggregateRoot $anAggregate
     * @return \Prooph\EventStore\EventSourcing\AggregateChangedEvent[]
     */
    public function extractPendingEvents(EventSourcedAggregateRoot $anAggregate)
    {
        return $anAggregate->getPendingEvents();
    }

    /**
     * @param EventSourcedAggregateRoot $anAggregate
     * @return mixed AggregateId
     */
    public function getAggregateId(EventSourcedAggregateRoot $anAggregate)
    {
        $result = $anAggregate->getLifeCycleEvents()->trigger(new GetIdentifierProperty($anAggregate));

        $property = $result->last();

        $aggregateRef = new \ReflectionClass($anAggregate);

        $propertyRef = $aggregateRef->getProperty($property);

        $propertyRef->setAccessible(true);

        return $propertyRef->getValue($anAggregate);
    }

    public function fromHistory($aggregatePrototype, $aggregateId, array $historyStream)
    {
        $aggregatePrototype->initializeFromHistory($aggregateId, $historyStream);

        return $aggregatePrototype;
    }
}
 