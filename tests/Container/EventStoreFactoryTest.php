<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 21.08.15 - 17:19
 */

namespace Prooph\EventStoreTest\Container;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Container\EventStoreFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;

/**
 * Class EventStoreFactoryTest
 * @package Prooph\EventStoreTest\Container
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
     * @expectedException Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Event store adapter is missing in configuration
     */
    public function it_throws_exception_when_adapter_config_is_missing()
    {
        $config['prooph']['event_store'] = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);

        $factory = new EventStoreFactory();
        $factory($containerMock);
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
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Missing prooph config key in application config
     */
    public function it_throws_exception_when_no_prooph_config_key_set()
    {
        $config = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);

        $factory = new EventStoreFactory();
        $factory($containerMock);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Missing key event_store in prooph configuration
     */
    public function it_throws_exception_when_no_event_store_config_key_set()
    {
        $config['prooph'] = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);

        $factory = new EventStoreFactory();
        $factory($containerMock);
    }
}
