<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 01:28
 */

namespace Prooph\EventStore\Aggregate;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException;

/**
 * Class DefaultAggregateTranslator
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DefaultAggregateTranslator implements AggregateTranslator
{
    /**
     * @param object $anEventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return string
     */
    public function extractAggregateId($anEventSourcedAggregateRoot)
    {
        if (! method_exists($anEventSourcedAggregateRoot, 'id')) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method id does not exist for aggregate %s',
                    get_class($anEventSourcedAggregateRoot)
                )
            );
        }

        return (string)$anEventSourcedAggregateRoot->id();
    }

    /**
     * @param AggregateType $aggregateType
     * @param DomainEvent[] $historyEvents
     * @throws Exception\AggregateTranslationFailedException
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, array $historyEvents)
    {
        if (! class_exists($aggregateType->toString())) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not reconstitute aggregate of type %s. Class was not found',
                    $aggregateType->toString()
                )
            );
        }

        $refObj = new \ReflectionClass($aggregateType->toString());

        if (! $refObj->hasMethod('replay')) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Cannot reconstitute aggregate of type %s. Class is missing a replay method!',
                    $aggregateType->toString()
                )
            );
        }

        $aggregate = $refObj->newInstanceWithoutConstructor();

        $replay = $refObj->getMethod('replay');

        $replay->setAccessible(true);

        $replay->invoke($aggregate, $historyEvents);

        return $aggregate;
    }

    /**
     * @param object $anEventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return DomainEvent[]
     */
    public function extractPendingStreamEvents($anEventSourcedAggregateRoot)
    {
        $refObj = new \ReflectionClass($anEventSourcedAggregateRoot);

        if (! $refObj->hasMethod('popRecordedEvents')) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not extract pending events of aggregate %s. Class is missing a method with name popRecordedEvents!',
                    get_class($anEventSourcedAggregateRoot)
                )
            );
        }

        $popRecordedEventsMethod = $refObj->getMethod('popRecordedEvents');

        $popRecordedEventsMethod->setAccessible(true);

        $recordedEvents = $popRecordedEventsMethod->invoke($anEventSourcedAggregateRoot);

        return $recordedEvents;
    }
}
 