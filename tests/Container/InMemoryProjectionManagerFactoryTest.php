<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Container;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Container\InMemoryProjectionManagerFactory;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Psr\Container\ContainerInterface;

class InMemoryProjectionManagerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_projection_manager(): void
    {
        $config['prooph']['projection_manager']['default'] = [
            'event_store' => 'my_event_store',
        ];

        $eventStore = new InMemoryEventStore();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->get('my_event_store')->willReturn($eventStore)->shouldBeCalled();

        $factory = new InMemoryProjectionManagerFactory();
        $projectionManager = $factory($container->reveal());

        $this->assertInstanceOf(InMemoryProjectionManager::class, $projectionManager);
    }

    /**
     * @test
     */
    public function it_creates_projection_manager_via_callstatic(): void
    {
        $config['prooph']['projection_manager']['default'] = [
            'event_store' => 'my_event_store',
        ];

        $eventStore = new InMemoryEventStore();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->get('my_event_store')->willReturn($eventStore)->shouldBeCalled();

        $name = 'default';
        $projectionManager = InMemoryProjectionManagerFactory::$name($container->reveal());

        $this->assertInstanceOf(InMemoryProjectionManager::class, $projectionManager);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_given_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type = 'another';
        InMemoryProjectionManagerFactory::$type('invalid container');
    }
}
