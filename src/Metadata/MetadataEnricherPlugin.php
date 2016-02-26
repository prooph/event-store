<?php

/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Metadata;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Stream\Stream;

final class MetadataEnricherPlugin implements Plugin
{
    /**
     * @var MetadataEnricher
     */
    private $metadataEnricher;

    /**
     * @param MetadataEnricher $metadataEnricher
     */
    public function __construct(MetadataEnricher $metadataEnricher)
    {
        $this->metadataEnricher = $metadataEnricher;
    }

    /**
     * @param EventStore $eventStore
     */
    public function setUp(EventStore $eventStore)
    {
        $eventEmitter = $eventStore->getActionEventEmitter();

        $eventEmitter->attachListener('create.pre', [$this, 'onEventStoreCreateStream'], -1000);
        $eventEmitter->attachListener('appendTo.pre', [$this, 'onEventStoreAppendToStream'], -1000);
    }

    /**
     * Add event metadata on event store createStream.
     *
     * @param ActionEvent $createEvent
     */
    public function onEventStoreCreateStream(ActionEvent $createEvent)
    {
        $stream = $createEvent->getParam('stream');

        if (!$stream instanceof Stream) {
            return;
        }

        $streamEvents = $stream->streamEvents();
        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $createEvent->setParam('stream', new Stream($stream->streamName(), $streamEvents));
    }

    /**
     * Add event metadata on event store appendToStream.
     *
     * @param ActionEvent $appendToStreamEvent
     */
    public function onEventStoreAppendToStream(ActionEvent $appendToStreamEvent)
    {
        $streamEvents = $appendToStreamEvent->getParam('streamEvents');

        if (!$streamEvents instanceof \Iterator) {
            return;
        }

        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $appendToStreamEvent->setParam('streamEvents', $streamEvents);
    }

    /**
     * This method takes domain events as argument which are going to be added
     * to the event stream and add the metadata via the MetadataEnricher.
     *
     * @param Iterator $events
     *
     * @return Iterator
     */
    private function handleRecordedEvents(\Iterator $events)
    {
        $enrichedEvents = [];

        foreach ($events as $event) {
            $enrichedEvents[] = $this->metadataEnricher->enrich($event);
        }

        return new \ArrayIterator($enrichedEvents);
    }
}
