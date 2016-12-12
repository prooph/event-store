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

namespace ProophTest\EventStore\Metadata;

use Prooph\Common\Event\DefaultActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\ActionEventEmitterEventStoreTestCase;
use ProophTest\EventStore\Mock\UserCreated;
use Prophecy\Argument;

class MetadataEnricherPluginTest extends ActionEventEmitterEventStoreTestCase
{
    /**
     * @test
     */
    public function it_attaches_itself_to_event_store_events(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);

        $createStreamListener = null;
        $appendToStreamListener = null;

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $plugin->setUp($this->eventStore);

        $property = new \ReflectionProperty(get_class($this->eventStore->getActionEventEmitter()), 'events');
        $property->setAccessible(true);

        $this->assertCount(8, $property->getValue($this->eventStore->getActionEventEmitter()));
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_create(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $stream = new Stream(new StreamName('test'), new \ArrayIterator([$messageEvent]));
        $actionEvent = new DefaultActionEvent('create');
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
        $this->assertEquals(['foo' => 'bar', '_aggregate_version' => 1], $streamEvents[0]->metadata());
    }

    /**
     * @test
     */
    public function it_does_not_enrich_metadata_on_create_if_stream_is_not_set(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher->enrich(Argument::any())->shouldNotBeCalled();

        $actionEvent = new DefaultActionEvent('create');

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());
        $plugin->onEventStoreCreateStream($actionEvent);
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_appendTo(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $actionEvent = new DefaultActionEvent('appendTo');
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
        $this->assertEquals(['foo' => 'bar', '_aggregate_version' => 1], $streamEvents[0]->metadata());
    }

    /**
     * @test
     */
    public function it_does_not_enrich_metadata_on_appendTo_if_stream_is_not_set(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher->enrich(Argument::any())->shouldNotBeCalled();

        $actionEvent = new DefaultActionEvent('appendTo');

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());
        $plugin->onEventStoreAppendToStream($actionEvent);
    }
}
