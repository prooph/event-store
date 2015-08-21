<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
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
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Container\EventStoreFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;

/**
 * Class EventStoreFactoryTest
 * @package Prooph\EventStoreTest\Container
 */
class EventStoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_event_store()
    {
        $config['prooph']['event_store'] = [];

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')->with('config')->willReturn($config);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @test
     */
    public function it_injects_custom_adapter()
    {
        $config['prooph']['event_store']['adapter'] = 'adapter';

        $adapterMock = $this->getMockForAbstractClass(Adapter::class);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('adapter')->willReturn($adapterMock);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @test
     */
    public function it_injects_custom_event_emitter()
    {
        $config['prooph']['event_store']['event_emitter'] = 'event_emitter';

        $eventEmitterMock = $this->getMockForAbstractClass(ActionEventEmitter::class);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('event_emitter')->willReturn($eventEmitterMock);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @test
     */
    public function it_injects_plugins()
    {
        $config['prooph']['event_store']['plugins'][] = 'plugin';

        $featureMock = $this->getMockForAbstractClass(Feature::class);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new EventStoreFactory();
        $eventStore = $factory($containerMock);

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Feature plugin does not implement the Feature interface
     */
    public function it_throws_exception_when_invalid_plugin_configured()
    {
        $config['prooph']['event_store']['plugins'][] = 'plugin';

        $featureMock = 'foo';

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(1))->method('get')->with('plugin')->willReturn($featureMock);

        $factory = new EventStoreFactory();
        $factory($containerMock);
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Exception\ConfigurationException
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
     * @expectedException Prooph\EventStore\Exception\ConfigurationException
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
