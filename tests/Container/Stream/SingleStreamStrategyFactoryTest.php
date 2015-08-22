<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 21.08.15 - 17:17
 */

namespace Prooph\EventStoreTest\Container\Stream;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Container\Stream\SingleStreamStrategyFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\SingleStreamStrategy;

/**
 * Class AggregateTypeStreamStrategyFactory
 * @package Prooph\EventStoreTest\Container\Stream
 */
class SingleStreamStrategyFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_strategy()
    {
        $eventStoreMock = $this->getMockForAbstractClass(EventStore::class, [], '', false);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->once())->method('has')->with('config')->willReturn(false);
        $containerMock->expects($this->once())->method('get')->with('prooph.event_store')->willReturn($eventStoreMock);

        $factory = new SingleStreamStrategyFactory();
        $streamStrategy = $factory($containerMock);

        $this->assertInstanceOf(SingleStreamStrategy::class, $streamStrategy);
    }

    /**
     * @test
     */
    public function it_injects_aggregate_type_stream_map()
    {
        $config['prooph']['event_store']['single_stream_name'] = 'foobar';

        $eventStoreMock = $this->getMockForAbstractClass(EventStore::class, [], '', false);

        $containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $containerMock->expects($this->at(0))->method('has')->with('config')->willReturn(true);
        $containerMock->expects($this->at(1))->method('get')->with('config')->willReturn($config);
        $containerMock->expects($this->at(2))->method('get')->with('prooph.event_store')->willReturn($eventStoreMock);

        $factory = new SingleStreamStrategyFactory();
        $streamStrategy = $factory($containerMock);

        $this->assertInstanceOf(SingleStreamStrategy::class, $streamStrategy);
    }
}
