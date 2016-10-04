<?php
/**
 * This file is part of the prooph/service-bus.
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
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Container\EventStoreFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prophecy\Argument;

/**
 * Class EventStoreFactoryTest
 * @package ProophTest\EventStore\Container
 */
class EventStoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_event_store_with_default_event_emitter()
    {
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
        $this->assertInstanceOf(InMemoryAdapter::class, $eventStore->getAdapter());
        $this->assertInstanceOf(ProophActionEventEmitter::class, $eventStore->getActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_injects_custom_event_emitter()
    {
        $config['prooph']['event_store']['event_emitter'] = 'event_emitter';
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;

        $eventEmitterMock = $this->getMockForAbstractClass(ActionEventEmitter::class);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());
        $containerMock->expects($this->at(2))->method('get')->with('event_emitter')->willReturn($eventEmitterMock);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
        $this->assertSame($eventEmitterMock, $eventStore->getActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_injects_plugins()
    {
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;
        $config['prooph']['event_store']['plugins'][] = 'plugin';

        $featureMock = $this->getMockForAbstractClass(Plugin::class);
        $featureMock->expects($this->once())->method('setUp');

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());
        $containerMock->expects($this->at(2))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Plugin plugin does not implement the Plugin interface
     */
    public function it_throws_exception_when_invalid_plugin_configured()
    {
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;
        $config['prooph']['event_store']['plugins'][] = 'plugin';

        $featureMock = 'foo';

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());
        $containerMock->expects($this->at(2))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new EventStoreFactory();
        $factory($containerMock);
    }

    /**
     * @test
     */
    public function it_injects_metadata_enrichers()
    {
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;
        $config['prooph']['event_store']['metadata_enrichers'][] = 'metadata_enricher1';
        $config['prooph']['event_store']['metadata_enrichers'][] = 'metadata_enricher2';

        $metadataEnricher1 = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher2 = $this->prophesize(MetadataEnricher::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());
        $container->get('metadata_enricher1')->willReturn($metadataEnricher1->reveal());
        $container->get('metadata_enricher2')->willReturn($metadataEnricher2->reveal());

        $factory = new EventStoreFactory();
        $eventStore = $factory($container->reveal());

        $this->assertInstanceOf(EventStore::class, $eventStore, 'Event store should be correctly instancied');

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

        $eventStore->beginTransaction();
        $eventStore->create($stream);
        $eventStore->commit();
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Metadata enricher foobar does not implement the MetadataEnricher interface
     */
    public function it_throws_exception_when_invalid_metadata_enricher_configured()
    {
        $config['prooph']['event_store']['adapter']['type'] = InMemoryAdapter::class;
        $config['prooph']['event_store']['metadata_enrichers'][] = 'foobar';

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get(InMemoryAdapter::class)->willReturn(new InMemoryAdapter());
        $container->get('foobar')->willReturn(new \stdClass());

        $factory = new EventStoreFactory();
        $eventStore = $factory($container->reveal());
    }
}
