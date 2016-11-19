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
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;

final class InMemoryEventStore extends AbstractActionEventEmitterAwareEventStore
{
    /**
     * @var array
     */
    private $streams = [];

    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    public function __construct(ActionEventEmitter $actionEventEmitter)
    {
        $this->actionEventEmitter = $actionEventEmitter;

        $actionEventEmitter->attachListener(self::EVENT_CREATE, function (ActionEvent $event): void {
            $stream = $event->getParam('stream');

            if (isset($this->streams[$stream->streamName()->toString()])) {
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

            if (! isset($this->streams[$streamName->toString()])) {
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

            if (! isset($this->streams[$streamName->toString()])) {
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

            if (! isset($this->streams[$streamName->toString()])) {
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

        $actionEventEmitter->attachListener(self::EVENT_DELETE, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            unset($this->streams[$streamName->toString()]);

            $event->setParam('result', true);
        });

        $actionEventEmitter->attachListener(self::EVENT_HAS_STREAM, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            $result = isset($this->streams[$streamName->toString()]);

            $event->setParam('result', $result);
        });

        $actionEventEmitter->attachListener(self::EVENT_FETCH_STREAM_METADATA, function (ActionEvent $event): void {
            $streamName = $event->getParam('streamName');

            if (! isset($this->streams[$streamName->toString()])) {
                return;
            }

            $metadata = $this->streams[$streamName->toString()]['metadata'];

            $event->setParam('metadata', $metadata);
        });
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            $field = $match['field'];

            if (! isset($metadata[$field])) {
                return false;
            }

            $operator = $match['operator'];
            $expected = $match['value'];

            switch ($operator) {
                case Operator::EQUALS():
                    if ($metadata[$field] !== $expected) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN():
                    if (! ($metadata[$field] > $expected)) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN_EQUALS():
                    if (! ($metadata[$field] >= $expected)) {
                        return false;
                    };
                    break;
                case Operator::LOWER_THAN():
                    if (! ($metadata[$field] < $expected)) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN_EQUALS():
                    if (! ($metadata[$field] <= $expected)) {
                        return false;
                    }
                    break;
                case Operator::NOT_EQUALS():
                    if ($metadata[$field] === $expected) {
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
