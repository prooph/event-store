<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async;

use Amp\Promise;
use Prooph\EventStore\EventId;
use Prooph\EventStore\Internal\ResolvedEvent;
use Prooph\EventStore\PersistentSubscriptionNakEventAction;

interface EventStorePersistentSubscription
{
    public const DEFAULT_BUFFER_SIZE = 10;

    /**
     * @internal
     *
     * @return Promise<self>
     */
    public function start(): Promise;

    /**
     * Acknowledge that a message have completed processing (this will tell the server it has been processed)
     * Note: There is no need to ack a message if you have Auto Ack enabled
     *
     * @param ResolvedEvent $event
     *
     * @return void
     */
    public function acknowledge(ResolvedEvent $event): void;

    /**
     * Acknowledge that a message have completed processing (this will tell the server it has been processed)
     * Note: There is no need to ack a message if you have Auto Ack enabled
     *
     * @param ResolvedEvent[] $events
     *
     * @return void
     */
    public function acknowledgeMultiple(array $events): void;

    /**
     * Acknowledge that a message have completed processing (this will tell the server it has been processed)
     * Note: There is no need to ack a message if you have Auto Ack enabled
     *
     * @param EventId $eventId
     *
     * @return void
     */
    public function acknowledgeEventId(EventId $eventId): void;

    /**
     * Acknowledge that a message have completed processing (this will tell the server it has been processed)
     * Note: There is no need to ack a message if you have Auto Ack enabled
     *
     * @param EventId[] $eventIds
     *
     * @return void
     */
    public function acknowledgeMultipleEventIds(array $eventIds): void;

    /**
     * Mark a message failed processing. The server will be take action based upon the action paramter
     */
    public function fail(
        ResolvedEvent $event,
        PersistentSubscriptionNakEventAction $action,
        string $reason
    ): void;

    /**
     * Mark n messages that have failed processing. The server will take action based upon the action parameter
     *
     * @param ResolvedEvent[] $events
     * @param PersistentSubscriptionNakEventAction $action
     * @param string $reason
     */
    public function failMultiple(
        array $events,
        PersistentSubscriptionNakEventAction $action,
        string $reason
    ): void;

    /**
     * Mark a message failed processing. The server will be take action based upon the action paramter
     */
    public function failEventId(
        EventId $eventId,
        PersistentSubscriptionNakEventAction $action,
        string $reason
    ): void;

    /**
     * Mark n messages that have failed processing. The server will take action based upon the action parameter
     *
     * @param EventId[] $eventIds
     * @param PersistentSubscriptionNakEventAction $action
     * @param string $reason
     */
    public function failMultipleEventIds(
        array $eventIds,
        PersistentSubscriptionNakEventAction $action,
        string $reason
    ): void;

    public function stop(?int $timeout = null): Promise;
}
