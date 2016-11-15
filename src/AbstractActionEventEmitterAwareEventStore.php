<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Iterator;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Util\Assertion;

abstract class AbstractActionEventEmitterAwareEventStore implements EventStore, ActionEventEmitterAware
{
    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    public function getActionEventEmitter(): ActionEventEmitter
    {
        return $this->actionEventEmitter;
    }

    public function create(Stream $stream): void
    {
        $argv = ['stream' => $stream, 'streamEvents' => $stream->streamEvents()];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_CREATE, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if (! $event->getParam('result', false)) {
            throw StreamExistsAlready::with($stream->streamName());
        }
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        $argv = ['streamName' => $streamName, 'streamEvents' => $streamEvents];

        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_APPEND_TO, $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if (! $event->getParam('result', false)) {
            throw StreamNotFound::with($streamName);
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

        $stream = $event->getParam('stream', false);

        if ($stream instanceof Stream && $stream->streamName()->toString() === $streamName->toString()) {
            return $stream;
        }

        throw StreamNotFound::with($streamName);
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

        $stream = $event->getParam('stream', false);

        if ($stream instanceof Stream && $stream->streamName()->toString() === $streamName->toString()) {
            return $stream;
        }

        throw StreamNotFound::with($streamName);
    }

    public function delete(StreamName $streamName): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_DELETE, $this, ['streamName' => $streamName]);

        $this->actionEventEmitter->dispatch($event);

        if (! $event->getParam('result', false)) {
            throw new RuntimeException("Could not delete stream '$streamName'");
        }
    }
}
