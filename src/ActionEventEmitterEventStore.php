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
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjectionFactory;
use Prooph\EventStore\Util\Assertion;

class ActionEventEmitterEventStore implements EventStoreDecorator
{
    public const EVENT_APPEND_TO = 'appendTo';
    public const EVENT_CREATE = 'create';
    public const EVENT_LOAD = 'load';
    public const EVENT_LOAD_REVERSE = 'loadReverse';
    public const EVENT_DELETE = 'delete';
    public const EVENT_HAS_STREAM = 'hasStream';
    public const EVENT_FETCH_STREAM_METADATA = 'fetchStreamMetadata';
    public const EVENT_UPDATE_STREAM_METADATA = 'updateStreamMetadata';
    public const EVENT_DELETE_PROJECTION = 'deleteProjection';
    public const EVENT_RESET_PROJECTION = 'resetProjection';
    public const EVENT_STOP_PROJECTION = 'stopProjection';
    public const EVENT_FETCH_STREAM_NAMES = 'fetchStreamNames';
    public const EVENT_FETCH_CATEGORY_NAMES = 'fetchCategoryNames';
    public const EVENT_FETCH_PROJECTION_NAMES = 'fetchProjectionNames';
    public const EVENT_FETCH_PROJECTION_STATUS = 'fetchProjectionStatus';
    public const EVENT_FETCH_PROJECTION_STREAM_POSITIONS = 'fetchProjectionStreamPositions';
    public const EVENT_FETCH_PROJECTION_STATE = 'fetchProjectionState';

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
                $streamEvents = $this->eventStore->load($streamName, $fromNumber, $count, $metadataMatcher);
                $event->setParam('streamEvents', $streamEvents);
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
                $streamEvents = $this->eventStore->loadReverse($streamName, $fromNumber, $count, $metadataMatcher);
                $event->setParam('streamEvents', $streamEvents);
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

        $actionEventEmitter->attachListener(self::EVENT_DELETE_PROJECTION, function (ActionEvent $event): void {
            $name = $event->getParam('name');
            $deleteEmittedEvents = $event->getParam('deleteEmittedEvents');

            $this->eventStore->deleteProjection($name, $deleteEmittedEvents);
        });

        $actionEventEmitter->attachListener(self::EVENT_RESET_PROJECTION, function (ActionEvent $event): void {
            $name = $event->getParam('name');

            $this->eventStore->resetProjection($name);
        });

        $actionEventEmitter->attachListener(self::EVENT_STOP_PROJECTION, function (ActionEvent $event): void {
            $name = $event->getParam('name');

            $this->eventStore->stopProjection($name);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_STREAM_NAMES, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $regex = $event->getParam('regex');
            $metadataMatcher = $event->getParam('metadataMatcher');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchStreamNames($filter, $regex, $metadataMatcher, $limit, $offset);

            $event->setParam('streamNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_CATEGORY_NAMES, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $regex = $event->getParam('regex');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchCategoryNames($filter, $regex, $limit, $offset);

            $event->setParam('categoryNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_PROJECTION_NAMES, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $regex = $event->getParam('regex');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchProjectionNames($filter, $regex, $limit, $offset);

            $event->setParam('projectionNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_PROJECTION_STATUS, function (ActionEvent $event): void {
            $name = $event->getParam('name');

            $status = $this->eventStore->fetchProjectionStatus($name);

            $event->setParam('status', $status);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_PROJECTION_STREAM_POSITIONS, function (ActionEvent $event): void {
            $name = $event->getParam('name');

            $streamPositions = $this->eventStore->fetchProjectionStreamPositions($name);

            $event->setParam('streamPositions', $streamPositions);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_PROJECTION_STATE, function (ActionEvent $event): void {
            $name = $event->getParam('name');

            $state = $this->eventStore->fetchProjectionState($name);

            $event->setParam('state', $state);
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
    ): Iterator {
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

        $stream = $event->getParam('streamEvents', false);

        if (! $stream instanceof Iterator) {
            throw StreamNotFound::with($streamName);
        }

        return $stream;
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
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

        $stream = $event->getParam('streamEvents', false);

        if (! $stream instanceof Iterator) {
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

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_DELETE_PROJECTION,
            $this,
            [
                'name' => $name,
                'deleteEmittedEvents' => $deleteEmittedEvents,
            ]
        );

        $this->actionEventEmitter->dispatch($event);
    }

    public function resetProjection(string $name): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_RESET_PROJECTION,
            $this,
            ['name' => $name]
        );

        $this->actionEventEmitter->dispatch($event);
    }

    public function stopProjection(string $name): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_STOP_PROJECTION,
            $this,
            ['name' => $name]
        );

        $this->actionEventEmitter->dispatch($event);
    }

    public function fetchStreamNames(
        ?string $filter,
        bool $regex,
        ?MetadataMatcher $metadataMatcher,
        int $limit,
        int $offset
    ): array {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_STREAM_NAMES,
            $this,
            [
                'filter' => $filter,
                'regex' => $regex,
                'metadataMatcher' => $metadataMatcher,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('streamNames', []);
    }

    public function fetchCategoryNames(?string $filter, bool $regex, int $limit, int $offset): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_CATEGORY_NAMES,
            $this,
            [
                'filter' => $filter,
                'regex' => $regex,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('categoryNames', []);
    }

    public function fetchProjectionNames(?string $filter, bool $regex, int $limit, int $offset): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_PROJECTION_NAMES,
            $this,
            [
                'filter' => $filter,
                'regex' => $regex,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('projectionNames', []);
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_PROJECTION_STATUS,
            $this,
            [
                'name' => $name,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('status');
    }

    public function fetchProjectionStreamPositions(string $name): ?array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_PROJECTION_STREAM_POSITIONS,
            $this,
            [
                'name' => $name,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('streamPositions');
    }

    public function fetchProjectionState(string $name): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_PROJECTION_STATE,
            $this,
            [
                'name' => $name,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('state', []);
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
