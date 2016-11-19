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

namespace ProophTest\EventStore\Container;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Container\InMemoryEventStoreFactory;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\InMemoryEventStore;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prophecy\Argument;

class InMemoryEventStoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_event_store_with_default_event_emitter(): void
    {
        $config['prooph']['event_store']['default'] = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);

        $factory = new InMemoryEventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(InMemoryEventStore::class, $eventStore);
        $this->assertInstanceOf(ProophActionEventEmitter::class, $eventStore->getActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_creates_event_store_with_default_event_emitter_via_callstatic(): void
    {
        $config['prooph']['event_store']['another'] = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);

        $type = 'another';
        $eventStore = InMemoryEventStoreFactory::$type($containerMock);

        $this->assertInstanceOf(InMemoryEventStore::class, $eventStore);
        $this->assertInstanceOf(ProophActionEventEmitter::class, $eventStore->getActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_injects_custom_event_emitter(): void
    {
        $config['prooph']['event_store']['default']['event_emitter'] = 'event_emitter';

        $eventEmitterMock = $this->getMockForAbstractClass(ActionEventEmitter::class);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('event_emitter')->willReturn($eventEmitterMock);

        $factory = new InMemoryEventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(InMemoryEventStore::class, $eventStore);
        $this->assertSame($eventEmitterMock, $eventStore->getActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_injects_plugins(): void
    {
        $config['prooph']['event_store']['default']['plugins'][] = 'plugin';

        $featureMock = $this->getMockForAbstractClass(Plugin::class);
        $featureMock->expects($this->once())->method('setUp');

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new InMemoryEventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(InMemoryEventStore::class, $eventStore);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_plugin_configured(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Plugin plugin does not implement the Plugin interface');

        $config['prooph']['event_store']['default']['plugins'][] = 'plugin';

        $featureMock = 'foo';

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new InMemoryEventStoreFactory();
        $factory($containerMock);
    }

    /**
     * @test
     */
    public function it_injects_metadata_enrichers(): void
    {
        $config['prooph']['event_store']['default']['metadata_enrichers'][] = 'metadata_enricher1';
        $config['prooph']['event_store']['default']['metadata_enrichers'][] = 'metadata_enricher2';

        $metadataEnricher1 = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher2 = $this->prophesize(MetadataEnricher::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get('metadata_enricher1')->willReturn($metadataEnricher1->reveal());
        $container->get('metadata_enricher2')->willReturn($metadataEnricher2->reveal());

        $factory = new InMemoryEventStoreFactory();
        $eventStore = $factory($container->reveal());

        $this->assertInstanceOf(InMemoryEventStore::class, $eventStore);

        // Some events to inject into the event store
        $events = [
            UserCreated::with(['name' => 'John'], 1),
            UsernameChanged::with(['name' => 'Jane'], 2),
        ];

        // The metadata enrichers should be called as many
        // times as there are events
        $metadataEnricher1
            ->enrich(Argument::type(Message::class))
            ->shouldBeCalledTimes(count($events))
            ->willReturnArgument(0);

        $metadataEnricher2
            ->enrich(Argument::type(Message::class))
            ->shouldBeCalledTimes(count($events))
            ->willReturnArgument(0);

        $stream = new Stream(new StreamName('test'), new \ArrayIterator($events));

        /* @var InMemoryEventStore $eventStore */
        $eventStore->create($stream);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_metadata_enricher_configured(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metadata enricher foobar does not implement the MetadataEnricher interface');

        $config['prooph']['event_store']['default']['metadata_enrichers'][] = 'foobar';

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get('foobar')->willReturn(new \stdClass());

        $factory = new InMemoryEventStoreFactory();
        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_given_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type = 'another';
        InMemoryEventStoreFactory::$type('invalid container');
    }
}
