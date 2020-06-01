<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async;

use Amp\Promise;
use Closure;
use Prooph\EventStore\AllEventsSlice;
use Prooph\EventStore\CatchUpSubscriptionSettings;
use Prooph\EventStore\ConditionalWriteResult;
use Prooph\EventStore\DeleteResult;
use Prooph\EventStore\EventData;
use Prooph\EventStore\EventReadResult;
use Prooph\EventStore\EventStoreSubscription;
use Prooph\EventStore\ListenerHandler;
use Prooph\EventStore\PersistentSubscriptionCreateResult;
use Prooph\EventStore\PersistentSubscriptionDeleteResult;
use Prooph\EventStore\PersistentSubscriptionSettings;
use Prooph\EventStore\PersistentSubscriptionUpdateResult;
use Prooph\EventStore\Position;
use Prooph\EventStore\RawStreamMetadataResult;
use Prooph\EventStore\ResolvedEvent;
use Prooph\EventStore\StreamEventsSlice;
use Prooph\EventStore\StreamMetadata;
use Prooph\EventStore\StreamMetadataResult;
use Prooph\EventStore\SubscriptionDropReason;
use Prooph\EventStore\SystemSettings;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStore\WriteResult;
use Throwable;

interface EventStoreConnection
{
    public function connectionName(): string;

    /** @return Promise<void> */
    public function connectAsync(): Promise;

    public function close(): void;

    /** @return Promise<DeleteResult> */
    public function deleteStreamAsync(
        string $stream,
        int $expectedVersion,
        bool $hardDelete = false,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param list<EventData> $events
     * @return Promise<WriteResult>
     */
    public function appendToStreamAsync(
        string $stream,
        int $expectedVersion,
        array $events = [],
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param list<EventData> $events
     * @return Promise<ConditionalWriteResult>
     */
    public function conditionalAppendToStreamAsync(
        string $stream,
        int $expectedVersion,
        array $events = [],
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<EventReadResult> */
    public function readEventAsync(
        string $stream,
        int $eventNumber,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<StreamEventsSlice> */
    public function readStreamEventsForwardAsync(
        string $stream,
        int $start,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<StreamEventsSlice> */
    public function readStreamEventsBackwardAsync(
        string $stream,
        int $start,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<AllEventsSlice> */
    public function readAllEventsForwardAsync(
        Position $position,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<AllEventsSlice> */
    public function readAllEventsBackwardAsync(
        Position $position,
        int $count,
        bool $resolveLinkTos = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<WriteResult> */
    public function setStreamMetadataAsync(
        string $stream,
        int $expectedMetaStreamVersion,
        ?StreamMetadata $metadata = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<WriteResult> */
    public function setRawStreamMetadataAsync(
        string $stream,
        int $expectedMetaStreamVersion,
        string $metadata = '',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<StreamMetadataResult> */
    public function getStreamMetadataAsync(string $stream, ?UserCredentials $userCredentials = null): Promise;

    /** @return Promise<RawStreamMetadataResult> */
    public function getRawStreamMetadataAsync(string $stream, ?UserCredentials $userCredentials = null): Promise;

    /** @return Promise<WriteResult> */
    public function setSystemSettingsAsync(SystemSettings $settings, ?UserCredentials $userCredentials = null): Promise;

    /** @return Promise<EventStoreTransaction> */
    public function startTransactionAsync(
        string $stream,
        int $expectedVersion,
        ?UserCredentials $userCredentials = null
    ): Promise;

    public function continueTransaction(
        int $transactionId,
        ?UserCredentials $userCredentials = null
    ): EventStoreTransaction;

    /** @return Promise<PersistentSubscriptionCreateResult> */
    public function createPersistentSubscriptionAsync(
        string $stream,
        string $groupName,
        PersistentSubscriptionSettings $settings,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<PersistentSubscriptionUpdateResult> */
    public function updatePersistentSubscriptionAsync(
        string $stream,
        string $groupName,
        PersistentSubscriptionSettings $settings,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /** @return Promise<PersistentSubscriptionDeleteResult> */
    public function deletePersistentSubscriptionAsync(
        string $stream,
        string $groupName,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(EventStoreSubscription, ResolvedEvent): Promise $eventAppeared
     * @param null|Closure(EventStoreSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     * @param null|UserCredentials $userCredentials
     * @return Promise<EventStoreSubscription>
     */
    public function subscribeToStreamAsync(
        string $stream,
        bool $resolveLinkTos,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(EventStoreCatchUpSubscription, ResolvedEvent): Promise $eventAppeared
     * @param null|Closure(EventStoreCatchUpSubscription): void $liveProcessingStarted
     * @param null|Closure(EventStoreCatchUpSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     * @return Promise<EventStoreStreamCatchUpSubscription>
     */
    public function subscribeToStreamFromAsync(
        string $stream,
        ?int $lastCheckpoint,
        ?CatchUpSubscriptionSettings $settings,
        Closure $eventAppeared,
        ?Closure $liveProcessingStarted = null,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(EventStoreSubscription, ResolvedEvent): Promise $eventAppeared
     * @param null|Closure(EventStoreSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     * @return Promise<EventStoreSubscription>
     */
    public function subscribeToAllAsync(
        bool $resolveLinkTos,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(EventStoreCatchUpSubscription, ResolvedEvent): Promise $eventAppeared
     * @param null|Closure(EventStoreCatchUpSubscription): void $liveProcessingStarted
     * @param null|Closure(EventStoreCatchUpSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     * @return Promise<EventStoreAllCatchUpSubscription>
     */
    public function subscribeToAllFromAsync(
        ?Position $lastCheckpoint,
        ?CatchUpSubscriptionSettings $settings,
        Closure $eventAppeared,
        ?Closure $liveProcessingStarted = null,
        ?Closure $subscriptionDropped = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(EventStorePersistentSubscription, ResolvedEvent, null|int): Promise $eventAppeared
     * @param null|Closure(EventStorePersistentSubscription, SubscriptionDropReason, null|Throwable): void $subscriptionDropped
     *
     * @return Promise<EventStorePersistentSubscription>
     */
    public function connectToPersistentSubscriptionAsync(
        string $stream,
        string $groupName,
        Closure $eventAppeared,
        ?Closure $subscriptionDropped = null,
        int $bufferSize = 10,
        bool $autoAck = true,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * @param Closure(ClientConnectionEventArgs): void $handler
     */
    public function onConnected(Closure $handler): ListenerHandler;

    /**
     * @param Closure(ClientConnectionEventArgs): void $handler
     */
    public function onDisconnected(Closure $handler): ListenerHandler;

    /**
     * @param Closure(ClientReconnectingEventArgs): void $handler
     */
    public function onReconnecting(Closure $handler): ListenerHandler;

    /**
     * @param Closure(ClientClosedEventArgs): void $handler
     */
    public function onClosed(Closure $handler): ListenerHandler;

    /**
     * @param Closure(ClientErrorEventArgs): void $handler
     */
    public function onErrorOccurred(Closure $handler): ListenerHandler;

    /**
     * @param Closure(ClientAuthenticationFailedEventArgs): void $handler
     */
    public function onAuthenticationFailed(Closure $handler): ListenerHandler;

    public function detach(ListenerHandler $handler): void;
}
