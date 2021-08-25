<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Closure;
use Throwable;

interface EventStoreConnection
{
    public function deleteStream(
        string $stream,
        int $expectedVersion,
        bool $hardDelete = false,
        ?UserCredentials $userCredentials = null
    ): DeleteResult;

    /**
     * @param list<EventData> $events
     *
     * @return WriteResult
     */
    public function appendToStream(
        string $stream,
        int $expectedVersion,
        array $events = [],
        ?UserCredentials $userCredentials = null
    ): WriteResult;

    public function conditionalAppendToStream(
        string $stream,
        int $expectedVersion,
        array $events = [],
        ?UserCredentials $userCredentials = null
    ): ConditionalWriteResult;

    public function readEvent(
        string $stream,
        int $eventNumber,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): EventReadResult;

    public function readStreamEventsForward(
        string $stream,
        int $start,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): StreamEventsSlice;

    public function readStreamEventsBackward(
        string $stream,
        int $start,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): StreamEventsSlice;

    public function readAllEventsForward(
        Position $position,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): AllEventsSlice;

    public function readAllEventsBackward(
        Position $position,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): AllEventsSlice;

    public function setStreamMetadata(
        string $stream,
        int $expectedMetaStreamVersion,
        ?StreamMetadata $metadata = null,
        ?UserCredentials $userCredentials = null
    ): WriteResult;

    public function setRawStreamMetadata(
        string $stream,
        int $expectedMetaStreamVersion,
        string $metadata = '',
        ?UserCredentials $userCredentials = null
    ): WriteResult;

    public function getStreamMetadata(string $stream, ?UserCredentials $userCredentials = null): StreamMetadataResult;

    public function getRawStreamMetadata(string $stream, ?UserCredentials $userCredentials = null): RawStreamMetadataResult;

    public function setSystemSettings(SystemSettings $settings, ?UserCredentials $userCredentials = null): WriteResult;

    public function startTransaction(
        string $stream,
        int $expectedVersion,
        ?UserCredentials $userCredentials = null
    ): EventStoreTransaction;

    public function continueTransaction(
        int $transactionId,
        ?UserCredentials $userCredentials = null
    ): EventStoreTransaction;

    public function createPersistentSubscription(
        string $stream,
        string $groupName,
        PersistentSubscriptionSettings $settings,
        ?UserCredentials $userCredentials = null
    ): PersistentSubscriptionCreateResult;

    public function updatePersistentSubscription(
        string $stream,
        string $groupName,
        PersistentSubscriptionSettings $settings,
        ?UserCredentials $userCredentials = null
    ): PersistentSubscriptionUpdateResult;

    public function deletePersistentSubscription(
        string $stream,
        string $groupName,
        ?UserCredentials $userCredentials = null
    ): PersistentSubscriptionDeleteResult;

    /**
     * @param Closure(EventStoreSubscription, ResolvedEvent): void $eventAppeared
     * @param null|Closure(EventStoreSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     */
    public function subscribeToStream(
        string $stream,
        bool $resolveLinkTos,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): EventStoreSubscription;

    /**
     * @param Closure(EventStoreCatchUpSubscription, ResolvedEvent): void $eventAppeared
     * @param null|Closure(EventStoreCatchUpSubscription): void $liveProcessingStarted
     * @param null|Closure(EventStoreCatchUpSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     */
    public function subscribeToStreamFrom(
        string $stream,
        ?int $lastCheckpoint,
        ?CatchUpSubscriptionSettings $settings,
        Closure $eventAppeared,
        ?Closure $liveProcessingStarted = null,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): EventStoreStreamCatchUpSubscription;

    /**
     * @param Closure(EventStoreSubscription, ResolvedEvent): void $eventAppeared
     * @param null|Closure(EventStoreSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     */
    public function subscribeToAll(
        bool $resolveLinkTos,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): EventStoreSubscription;

    /**
     * @param Closure(EventStoreCatchUpSubscription, ResolvedEvent): void $eventAppeared
     * @param null|Closure(EventStoreCatchUpSubscription): void $liveProcessingStarted
     * @param null|Closure(EventStoreCatchUpSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     */
    public function subscribeToAllFrom(
        ?Position $lastCheckpoint,
        ?CatchUpSubscriptionSettings $settings,
        Closure $eventAppeared,
        ?Closure $liveProcessingStarted = null,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): EventStoreAllCatchUpSubscription;

    /**
     * @param Closure(EventStorePersistentSubscription, ResolvedEvent, null|int): void $eventAppeared
     * @param null|Closure(EventStorePersistentSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     */
    public function connectToPersistentSubscription(
        string $stream,
        string $groupName,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        int $bufferSize = 10,
        bool $autoAck = true,
        ?UserCredentials $userCredentials = null
    ): EventStorePersistentSubscription;
}
