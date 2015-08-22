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

use Prooph\Common\Messaging\Message;
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
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return string
     */
    public function extractAggregateId($eventSourcedAggregateRoot)
    {
        if (! method_exists($eventSourcedAggregateRoot, 'id')) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method id does not exist for aggregate %s',
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        return (string)$eventSourcedAggregateRoot->id();
    }

    /**
     * @param AggregateType $aggregateType
     * @param Message[] $historyEvents
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
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return Message[]
     */
    public function extractPendingStreamEvents($eventSourcedAggregateRoot)
    {
        $refObj = new \ReflectionClass($eventSourcedAggregateRoot);

        if (! $refObj->hasMethod('popRecordedEvents')) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not extract pending events of aggregate %s. Class is missing a method with name popRecordedEvents!',
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        $popRecordedEventsMethod = $refObj->getMethod('popRecordedEvents');

        $popRecordedEventsMethod->setAccessible(true);

        $recordedEvents = $popRecordedEventsMethod->invoke($eventSourcedAggregateRoot);

        return $recordedEvents;
    }
}
