<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/26/15 - 9:08 PM
 */
namespace ProophTest\EventStore\Container\Snapshot;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Container\Snapshot\SnapshotStoreFactory;
use Prooph\EventStore\Snapshot\Adapter\Adapter;
use Prooph\EventStore\Snapshot\SnapshotStore;
use ProophTest\EventStore\TestCase;

final class SnapshotStoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_snapshot_store()
    {
        $snapshotAdapter = $this->prophesize(Adapter::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'prooph' => [
                'snapshot_store' => [
                    'adapter' => [
                        'type' => 'mock_adapter'
                    ]
                ]
            ]
        ]);

        $container->get('mock_adapter')->willReturn($snapshotAdapter->reveal());

        $factory = new SnapshotStoreFactory();

        $snapshotStore = $factory($container->reveal());

        $this->assertInstanceOf(SnapshotStore::class, $snapshotStore);
    }
}
