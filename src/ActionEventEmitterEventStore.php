<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
    public const EVENT_FETCH_STREAM_NAMES = 'fetchStreamNames';
    public const EVENT_FETCH_STREAM_NAMES_REGEX = 'fetchStreamNamesRegex';
    public const EVENT_FETCH_CATEGORY_NAMES = 'fetchCategoryNames';
    public const EVENT_FETCH_CATEGORY_NAMES_REGEX = 'fetchCategoryNamesRegex';

    public const ALL_EVENTS = [
        self::EVENT_APPEND_TO,
        self::EVENT_CREATE,
        self::EVENT_LOAD,
        self::EVENT_LOAD_REVERSE,
        self::EVENT_DELETE,
        self::EVENT_HAS_STREAM,
        self::EVENT_FETCH_STREAM_METADATA,
        self::EVENT_UPDATE_STREAM_METADATA,
        self::EVENT_FETCH_STREAM_NAMES,
        self::EVENT_FETCH_STREAM_NAMES_REGEX,
        self::EVENT_FETCH_CATEGORY_NAMES,
        self::EVENT_FETCH_CATEGORY_NAMES_REGEX,
    ];

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

        $actionEventEmitter->attachListener(self::EVENT_FETCH_STREAM_NAMES, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $metadataMatcher = $event->getParam('metadataMatcher');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchStreamNames($filter, $metadataMatcher, $limit, $offset);

            $event->setParam('streamNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_STREAM_NAMES_REGEX, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $metadataMatcher = $event->getParam('metadataMatcher');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchStreamNamesRegex($filter, $metadataMatcher, $limit, $offset);

            $event->setParam('streamNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_CATEGORY_NAMES, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchCategoryNames($filter, $limit, $offset);

            $event->setParam('categoryNames', $streamNames);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_CATEGORY_NAMES_REGEX, function (ActionEvent $event): void {
            $filter = $event->getParam('filter');
            $limit = $event->getParam('limit');
            $offset = $event->getParam('offset');

            $streamNames = $this->eventStore->fetchCategoryNamesRegex($filter, $limit, $offset);

            $event->setParam('categoryNames', $streamNames);
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
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        Assertion::nullOrGreaterOrEqualThan($fromNumber, 1);
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

    public function fetchStreamNames(
        ?string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_STREAM_NAMES,
            $this,
            [
                'filter' => $filter,
                'metadataMatcher' => $metadataMatcher,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('streamNames', []);
    }

    public function fetchStreamNamesRegex(
        string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_STREAM_NAMES_REGEX,
            $this,
            [
                'filter' => $filter,
                'metadataMatcher' => $metadataMatcher,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('streamNames', []);
    }

    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_CATEGORY_NAMES,
            $this,
            [
                'filter' => $filter,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('categoryNames', []);
    }

    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_FETCH_CATEGORY_NAMES_REGEX,
            $this,
            [
                'filter' => $filter,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $this->actionEventEmitter->dispatch($event);

        return $event->getParam('categoryNames', []);
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
