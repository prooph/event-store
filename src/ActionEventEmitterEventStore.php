<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Iterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ListenerHandler;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\ProjectionFactory;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjectionFactory;
use Prooph\EventStore\Util\Assertion;

class ActionEventEmitterEventStore implements EventStore, EventStoreDecorator
{
    public const EVENT_APPEND_TO = 'appendTo';
    public const EVENT_CREATE = 'create';
    public const EVENT_LOAD = 'load';
    public const EVENT_LOAD_REVERSE = 'loadReverse';
    public const EVENT_DELETE = 'delete';
    public const EVENT_HAS_STREAM = 'hasStream';
    public const EVENT_FETCH_STREAM_METADATA = 'fetchStreamMetadata';
    public const EVENT_UPDATE_STREAM_METADATA = 'updateStreamMetadata';

    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    /**
     * @var EventStore
     */
    protected $eventStore;

    public function __construct(EventStore $eventStore, ActionEventEmitter $actionEventEmitter)
    {
        $this->eventStore = $eventStore;
        $this->actionEventEmitter = $actionEventEmitter;

        $actionEventEmitter->attachListener(self::EVENT_CREATE, function (ActionEvent $event): void {
            $stream = $event->getParam('stream');

            try {
                $this->eventStore->create($stream);
            } catch (StreamExistsAlready $exception) {
                $event->setParam('streamExistsAlready', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_APPEND_TO, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $streamEvents = $event->getParam('streamEvents');

            try {
                $this->eventStore->appendTo($streamName, $streamEvents);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            } catch (ConcurrencyException $exception) {
                $event->setParam('concurrencyException', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_LOAD, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $fromNumber = $event->getParam('fromNumber');
            $count = $event->getParam('count');
            $metadataMatcher = $event->getParam('metadataMatcher');

            try {
                $stream = $this->eventStore->load($streamName, $fromNumber, $count, $metadataMatcher);
                $event->setParam('stream', $stream);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_LOAD_REVERSE, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $fromNumber = $event->getParam('fromNumber');
            $count = $event->getParam('count');
            $metadataMatcher = $event->getParam('metadataMatcher');

            try {
                $stream = $this->eventStore->loadReverse($streamName, $fromNumber, $count, $metadataMatcher);
                $event->setParam('stream', $stream);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_DELETE, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            try {
                $this->eventStore->delete($streamName);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_HAS_STREAM, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            $event->setParam('result', $this->eventStore->hasStream($streamName));
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_STREAM_METADATA, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            try {
                $metadata = $this->eventStore->fetchStreamMetadata($streamName);
                $event->setParam('metadata', $metadata);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_UPDATE_STREAM_METADATA, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $metadata = $event->getParam('metadata');

            try {
                $this->eventStore->updateStreamMetadata($streamName, $metadata);
            } catch (StreamNotFound $exception) {
                $event->setParam('streamNotFound', true);
            }
        });
    }

    public function create(Stream $stream): void
    {
        $argv = ['stream' => $stream];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_CREATE, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamExistsAlready', false)) {
            throw StreamExistsAlready::with($stream->streamName());
        }
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        $argv = ['streamName' => $streamName, 'streamEvents' => $streamEvents];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_APPEND_TO, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }

        if ($event->getParam('concurrencyException', false)) {
            throw new ConcurrencyException();
        }
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream {
        Assertion::greaterOrEqualThan($fromNumber, 1);
        Assertion::nullOrGreaterOrEqualThan($count, 1);

        $argv = [
            'streamName' => $streamName,
            'fromNumber' => $fromNumber,
            'count' => $count,
            'metadataMatcher' => $metadataMatcher,
        ];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_LOAD, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }

        $stream = $event->getParam('stream', false);

        if (! $stream instanceof Stream) {
            throw StreamNotFound::with($streamName);
        }

        return $stream;
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream {
        Assertion::greaterOrEqualThan($fromNumber, 1);
        Assertion::nullOrGreaterOrEqualThan($count, 1);

        $argv = [
            'streamName' => $streamName,
            'fromNumber' => $fromNumber,
            'count' => $count,
            'metadataMatcher' => $metadataMatcher,
        ];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_LOAD_REVERSE, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }

        $stream = $event->getParam('stream', false);

        if (! $stream instanceof Stream) {
            throw StreamNotFound::with($streamName);
        }

        return $stream;
    }

    public function delete(StreamName $streamName): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_DELETE, $this, ['streamName' => $streamName]);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }
    }

    public function hasStream(StreamName $streamName): bool
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_HAS_STREAM,
            $this,
            ['streamName' => $streamName]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('result', false);
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_STREAM_METADATA,
            $this,
            ['streamName' => $streamName]
        );

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }

        $metadata = $event->getParam('metadata', false);

        if (! is_array($metadata)) {
            throw StreamNotFound::with($streamName);
        }

        return $metadata;
    }

    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_UPDATE_STREAM_METADATA,
            $this,
            [
                'streamName' => $streamName,
                'metadata' => $newMetadata,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('streamNotFound', false)) {
            throw StreamNotFound::with($streamName);
        }
    }

    public function createQuery(QueryFactory $factory = null): Query
    {
        if (null === $factory) {
            $factory = $this->getDefaultQueryFactory();
        }

        return $factory($this);
    }

    public function createProjection(
        string $name,
        ProjectionOptions $options = null,
        ProjectionFactory $factory = null
    ): Projection {
        if (null === $factory) {
            $factory = $this->getDefaultProjectionFactory();
        }

        return $factory($this, $name, $options);
    }

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        ProjectionOptions $options = null,
        ReadModelProjectionFactory $factory = null
    ): ReadModelProjection {
        if (null === $factory) {
            $factory = $this->getDefaultReadModelProjectionFactory();
        }

        return $factory($this, $name, $readModel, $options);
    }

    public function getDefaultQueryFactory(): QueryFactory
    {
        return $this->eventStore->getDefaultQueryFactory();
    }

    public function getDefaultProjectionFactory(): ProjectionFactory
    {
        return $this->eventStore->getDefaultProjectionFactory();
    }

    public function getDefaultReadModelProjectionFactory(): ReadModelProjectionFactory
    {
        return $this->eventStore->getDefaultReadModelProjectionFactory();
    }

    public function attach(string $eventName, callable $listener, int $priority = 0): ListenerHandler
    {
        return $this->actionEventEmitter->attachListener($eventName, $listener, $priority);
    }

    public function detach(ListenerHandler $handler): void
    {
        $this->actionEventEmitter->detachListener($handler);
    }

    public function getInnerEventStore(): EventStore
    {
        return $this->eventStore;
    }
}
