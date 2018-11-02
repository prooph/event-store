<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Metadata;

use ArrayIterator;
use Iterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\AbstractPlugin;
use Prooph\EventStore\Stream;

final class MetadataEnricherPlugin extends AbstractPlugin
{
    /**
     * @var MetadataEnricher
     */
    private $metadataEnricher;

    public function __construct(MetadataEnricher $metadataEnricher)
    {
        $this->metadataEnricher = $metadataEnricher;
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            [$this, 'onEventStoreCreateStream'],
            1000
        );

        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            [$this, 'onEventStoreAppendToStream'],
            1000
        );
    }

    /**
     * Add event metadata on event store createStream.
     */
    public function onEventStoreCreateStream(ActionEvent $createEvent): void
    {
        $stream = $createEvent->getParam('stream');

        if (! $stream instanceof Stream) {
            return;
        }

        $streamEvents = $stream->streamEvents();
        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $createEvent->setParam('stream', new Stream($stream->streamName(), $streamEvents, $stream->metadata()));
    }

    /**
     * Add event metadata on event store appendToStream.
     */
    public function onEventStoreAppendToStream(ActionEvent $appendToStreamEvent): void
    {
        $streamEvents = $appendToStreamEvent->getParam('streamEvents');

        if (! $streamEvents instanceof Iterator) {
            return;
        }

        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $appendToStreamEvent->setParam('streamEvents', $streamEvents);
    }

    /**
     * This method takes domain events as argument which are going to be added
     * to the event stream and add the metadata via the MetadataEnricher.
     */
    private function handleRecordedEvents(Iterator $events): Iterator
    {
        $enrichedEvents = [];

        foreach ($events as $event) {
            $enrichedEvents[] = $this->metadataEnricher->enrich($event);
        }

        return new ArrayIterator($enrichedEvents);
    }
}
