<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/8/15 - 10:14 AM
 */
namespace Prooph\EventStore\Aggregate;

use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

final class InMemoryIdentityMapTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_aggregate_root_to_identity_map_and_flags_it_as_dirty_as_long_as_clean_up_is_not_called()
    {
        $identityMap = new InMemoryIdentityMap();

        $aggregateRoot = $this->prophesize(User::class);

        $aggregateType = AggregateType::fromAggregateRoot($aggregateRoot->reveal());

        $identityMap->add($aggregateType, '1', $aggregateRoot->reveal());

        $this->assertTrue($identityMap->has($aggregateType, '1'));

        $this->assertSame($aggregateRoot->reveal(), $identityMap->get($aggregateType, '1'));

        $dirtyAggregates = $identityMap->getAllDirtyAggregateRoots($aggregateType);

        $this->assertEquals(1, count($dirtyAggregates));

        $this->assertSame($aggregateRoot->reveal(), $dirtyAggregates['1']);

        $identityMap->cleanUp($aggregateType);

        $this->assertEquals(0, count($identityMap->getAllDirtyAggregateRoots($aggregateType)));
    }

    /**
     * @test
     */
    public function it_flags_aggregate_root_as_dirty_again_when_it_is_fetched_from_identity_map()
    {
        $identityMap = new InMemoryIdentityMap();

        $aggregateRoot = $this->prophesize(User::class);

        $aggregateType = AggregateType::fromAggregateRoot($aggregateRoot->reveal());

        $identityMap->add($aggregateType, '1', $aggregateRoot->reveal());

        $identityMap->cleanUp($aggregateType);

        $this->assertEquals(0, count($identityMap->getAllDirtyAggregateRoots($aggregateType)));

        $this->assertTrue($identityMap->has($aggregateType, '1'));

        $this->assertSame($aggregateRoot->reveal(), $identityMap->get($aggregateType, '1'));

        $dirtyAggregates = $identityMap->getAllDirtyAggregateRoots($aggregateType);

        $this->assertEquals(1, count($dirtyAggregates));

        $this->assertSame($aggregateRoot->reveal(), $dirtyAggregates['1']);
    }
}
