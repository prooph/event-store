<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 21.08.15 - 17:01
 */

namespace ProophTest\EventStoreTest\Container\Stream;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Container\Stream\AggregateStreamStrategyFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\AggregateStreamStrategy;

/**
 * Class AggregateStreamStrategyFactoryTest
 * @package ProophTest\EventStore\Container\Stream
 */
class AggregateStreamStrategyFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_strategy()
    {
        $eventStoreMock = $this->getMockForAbstractClass(EventStore::class, [], '', false);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->once())->method('has')->with('config')->willReturn(false);
        $containerMock->expects($this->once())->method('get')->with(EventStore::class)->willReturn($eventStoreMock);

        $factory = new AggregateStreamStrategyFactory();
        $streamStrategy = $factory($containerMock);

        $this->assertInstanceOf(AggregateStreamStrategy::class, $streamStrategy);
    }

    /**
     * @test
     */
    public function it_injects_aggregate_type_stream_map()
    {
        $config['prooph']['event_store']['aggregate_type_stream_map'] = [];

        $eventStoreMock = $this->getMockForAbstractClass(EventStore::class, [], '', false);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('has')->with('config')->willReturn(true);
        $containerMock->expects($this->at(1))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(2))->method('get')->with(EventStore::class)->willReturn($eventStoreMock);

        $factory = new AggregateStreamStrategyFactory();
        $streamStrategy = $factory($containerMock);

        $this->assertInstanceOf(AggregateStreamStrategy::class, $streamStrategy);
    }
}
