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

namespace ProophTest\EventStore\Metadata;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\TestDomainEvent;
use Prophecy\Argument;

class MetadataEnricherPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_create(): void
    {
        $metadataEnricher = new class() implements MetadataEnricher {
            public function enrich(Message $message): Message
            {
                return $message->withAddedMetadata('foo', 'bar');
            }
        };

        $eventStore = new ActionEventEmitterEventStore(new InMemoryEventStore(), new ProophActionEventEmitter());

        $plugin = new MetadataEnricherPlugin($metadataEnricher);
        $plugin->attachToEventStore($eventStore);

        $eventStore->create(new Stream(new StreamName('foo'), new \ArrayIterator([new TestDomainEvent(['foo' => 'bar'])])));

        $streamEvents = $eventStore->load(new StreamName('foo'));

        $this->assertEquals(
            ['foo' => 'bar'],
            $streamEvents->current()->metadata()
        );
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
        $metadataEnricher = new class() implements MetadataEnricher {
            public function enrich(Message $message): Message
            {
                return $message->withAddedMetadata('foo', 'bar');
            }
        };

        $eventStore = new ActionEventEmitterEventStore(new InMemoryEventStore(), new ProophActionEventEmitter());

        $eventStore->create(new Stream(new StreamName('foo'), new \ArrayIterator()));

        $plugin = new MetadataEnricherPlugin($metadataEnricher);
        $plugin->attachToEventStore($eventStore);

        $eventStore->appendTo(new StreamName('foo'), new \ArrayIterator([new TestDomainEvent(['foo' => 'bar'])]));

        $streamEvents = $eventStore->load(new StreamName('foo'));

        $this->assertEquals(
            ['foo' => 'bar'],
            $streamEvents->current()->metadata()
        );
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

    /**
     * @test
     */
    public function it_detaches_from_event_store(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher->enrich(Argument::any())->shouldNotBeCalled();

        $eventStore = new ActionEventEmitterEventStore(new InMemoryEventStore(), new ProophActionEventEmitter());

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());
        $plugin->attachToEventStore($eventStore);
        $plugin->detachFromEventStore($eventStore);

        $eventStore->create(new Stream(new StreamName('foo'), new \ArrayIterator([new TestDomainEvent(['foo' => 'bar'])])));

        $stream = $eventStore->load(new StreamName('foo'));

        $this->assertEmpty($stream->current()->metadata());
    }
}
