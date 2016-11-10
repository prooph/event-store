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

use ArrayIterator;
use Iterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Util\Assertion;

final class InMemoryEventStore implements EventStore, ActionEventEmitterAware
{
    /**
     * @var array
     */
    protected $streams;

    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    public function __construct(ActionEventEmitter $actionEventEmitter)
    {
        $this->actionEventEmitter = $actionEventEmitter;

        $actionEventEmitter->attachListener(self::EVENT_CREATE, function (ActionEvent $event): void {
            $stream = $event->getParam('stream');

            if ($this->hasStream($stream->streamName())) {
                $event->stopPropagation();
                return;
            }

            foreach ($stream->streamEvents() as $streamEvent) {
                $this->streams[$stream->streamName()->toString()]['events'][] = $streamEvent;
            }

            $this->streams[$stream->streamName()->toString()]['metadata'] = $stream->metadata();

            $event->setParam('result', true);
        });

        $actionEventEmitter->attachListener(self::EVENT_APPEND_TO, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $streamEvents = $event->getParam('streamEvents');

            if (! $this->hasStream($streamName)) {
                $event->stopPropagation();
                return;
            }

            foreach ($streamEvents as $streamEvent) {
                $this->streams[$streamName->toString()]['events'][] = $streamEvent;
            }

            $event->setParam('result', true);
        });

        $actionEventEmitter->attachListener(self::EVENT_LOAD, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $fromNumber = $event->getParam('fromNumber');
            $count = $event->getParam('count');
            $metadataMatcher = $event->getParam('metadataMatcher');

            if (! $this->hasStream($streamName)) {
                $event->stopPropagation();
                return;
            }

            if (null === $metadataMatcher) {
                $metadataMatcher = new MetadataMatcher();
            }

            $streamEvents = [];

            foreach ($this->streams[$streamName->toString()]['events'] as $key => $streamEvent) {
                if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                    && ((null === $count
                            && ($key + 1) >= $fromNumber
                        ) || (null !== $count
                            && ($key + 1) >= $fromNumber
                            && ($key + 1) <= ($fromNumber + $count - 1)
                        )
                    )
                ) {
                    $streamEvents[] = $streamEvent;
                }
            }

            $event->setParam('stream', new Stream(
                $streamName,
                new ArrayIterator($streamEvents),
                $this->streams[$streamName->toString()]['metadata']
            ));
        });

        $actionEventEmitter->attachListener(self::EVENT_LOAD_REVERSE, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');
            $fromNumber = $event->getParam('fromNumber');
            $count = $event->getParam('count');
            $metadataMatcher = $event->getParam('metadataMatcher');

            if (! $this->hasStream($streamName)) {
                $event->stopPropagation();
                return;
            }

            if (null === $metadataMatcher) {
                $metadataMatcher = new MetadataMatcher();
            }

            $streamEvents = [];

            foreach ($this->streams[$streamName->toString()]['events'] as $key => $streamEvent) {
                if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                    && ((null === $count
                            && ($key + 1) <= $fromNumber
                        ) || (null !== $count
                            && ($key + 1) <= $fromNumber
                            && ($key + 1) >= ($fromNumber - $count + 1)
                        )
                    )
                ) {
                    $streamEvents[] = $streamEvent;
                }
            }

            $streamEvents = array_reverse($streamEvents);

            $event->setParam('stream', new Stream(
                $streamName,
                new ArrayIterator($streamEvents),
                $this->streams[$streamName->toString()]['metadata']
            ));
        });
    }

    public function getActionEventEmitter(): ActionEventEmitter
    {
        return $this->actionEventEmitter;
    }

    public function hasStream(StreamName $streamName): bool
    {
        return isset($this->streams[$streamName->toString()]);
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        if (! $this->hasStream($streamName)) {
            throw StreamNotFound::with($streamName);
        }

        return $this->streams[$streamName->toString()]['metadata'];
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

        $this->getActionEventEmitter()->dispatch($event);

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

        $this->getActionEventEmitter()->dispatch($event);

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

        $this->getActionEventEmitter()->dispatch($event);

        $stream = $event->getParam('stream', false);

        if ($stream instanceof Stream && $stream->streamName()->toString() === $streamName->toString()) {
            return $stream;
        }

        throw StreamNotFound::with($streamName);
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            $key = $match['key'];

            if (! isset($metadata[$key])) {
                return false;
            }

            $operator = $match['operator'];
            $expected = $match['value'];

            switch ($operator) {
                case Operator::EQUALS():
                    if ($metadata[$key] !== $expected) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN():
                    if (! ($metadata[$key] > $expected)) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN_EQUALS():
                    if (! ($metadata[$key] >= $expected)) {
                        return false;
                    };
                    break;
                case Operator::LOWER_THAN():
                    if (! ($metadata[$key] < $expected)) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN_EQUALS():
                    if (! ($metadata[$key] <= $expected)) {
                        return false;
                    }
                    break;
                case Operator::NOT_EQUALS():
                    if ($metadata[$key] === $expected) {
                        return false;
                    }
                    break;
                default:
                    throw new \UnexpectedValueException('Unknown operator found');
            }
        }

        return true;
    }
}
