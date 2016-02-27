<?php

/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Metadata;

use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\TestCase;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prophecy\Argument;

final class MetadataEnricherPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_attaches_itself_to_event_store_events()
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $eventStore = $this->prophesize(EventStore::class);
        $eventEmitter = $this->prophesize(ActionEventEmitter::class);

        $createStreamListener = null;
        $appendToStreamListener = null;

        $eventStore->getActionEventEmitter()->willReturn($eventEmitter);
        $eventEmitter->attachListener('create.pre', Argument::any(), -1000)->will(
            function ($args) use (&$createStreamListener) {
                $createStreamListener = $args[1];
            }
        );
        $eventEmitter->attachListener('appendTo.pre', Argument::any(), -1000)->will(
            function ($args) use (&$appendToStreamListener) {
                $appendToStreamListener = $args[1];
            }
        );

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $plugin->setUp($eventStore->reveal());

        $this->assertEquals([$plugin, 'onEventStoreCreateStream'], $createStreamListener);
        $this->assertEquals([$plugin, 'onEventStoreAppendToStream'], $appendToStreamListener);
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_create()
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $stream = new Stream(new StreamName('test'), new \ArrayIterator([$messageEvent]));
        $actionEvent = new DefaultActionEvent('create.pre');
        $actionEvent->setParam('stream', $stream);

        $metadataEnricher->enrich($messageEvent)->willReturn(
            $messageEvent->withAddedMetadata('foo', 'bar')
        );

        $plugin->onEventStoreCreateStream($actionEvent);

        // Assertion on event in the stream
        $streamEvents = $actionEvent->getParam('stream')->streamEvents();
        $this->assertCount(1, $streamEvents);
        $this->assertEquals($messageEvent->payload(), $streamEvents[0]->payload());
        $this->assertEquals($messageEvent->version(), $streamEvents[0]->version());
        $this->assertEquals($messageEvent->createdAt(), $streamEvents[0]->createdAt());
        $this->assertEquals(['foo' => 'bar'], $streamEvents[0]->metadata());
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_appendTo()
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $actionEvent = new DefaultActionEvent('appendTo.pre');
        $actionEvent->setParam('streamEvents', new \ArrayIterator([$messageEvent]));

        $metadataEnricher->enrich($messageEvent)->willReturn(
            $messageEvent->withAddedMetadata('foo', 'bar')
        );

        $plugin->onEventStoreAppendToStream($actionEvent);

        // Assertion on event in the stream
        $streamEvents = $actionEvent->getParam('streamEvents');
        $this->assertCount(1, $streamEvents);
        $this->assertEquals($messageEvent->payload(), $streamEvents[0]->payload());
        $this->assertEquals($messageEvent->version(), $streamEvents[0]->version());
        $this->assertEquals($messageEvent->createdAt(), $streamEvents[0]->createdAt());
        $this->assertEquals(['foo' => 'bar'], $streamEvents[0]->metadata());
    }
}
