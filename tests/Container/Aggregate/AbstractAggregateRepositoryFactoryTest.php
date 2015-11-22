<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/21/15 - 8:07 PM
 */
namespace ProophTest\EventStore\Container\Aggregate;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\EventStore\Stream\StreamStrategy;
use ProophTest\EventStore\Mock\FaultyRepositoryMock;
use ProophTest\EventStore\Mock\RepositoryMock;
use ProophTest\EventStore\Mock\RepositoryMockFactory;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\TestCase;

class AbstractAggregateRepositoryFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_repository_with_default_stream_name_and_no_snapshot_adapter()
    {
        $factory = new RepositoryMockFactory();

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => RepositoryMock::class,
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                    ]
                ]
            ]
        ]);

        $container->get(EventStore::class)->willReturn($this->eventStore);

        $userTranslator = $this->prophesize(AggregateTranslator::class);

        $container->get('user_translator')->willReturn($userTranslator->reveal());

        /** @var $repo RepositoryMock */
        $repo = $factory($container->reveal());

        $this->assertInstanceOf(RepositoryMock::class, $repo);

        $this->assertSame($this->eventStore, $repo->accessEventStore());
        $this->assertInstanceOf(AggregateType::class, $repo->accessAggregateType());
        $this->assertEquals(User::class, $repo->accessAggregateType()->toString());
        $this->assertSame($userTranslator->reveal(), $repo->accessAggregateTranslator());
        $this->assertEquals('event_stream', $repo->accessDeterminedStreamName()->toString());
        $this->assertNull($repo->accessSnapshotStore());
        $this->assertFalse($repo->accessOneStreamPerAggregateFlag());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Repository class UnknownClass cannot be found
     */
    public function it_throws_exception_if_repository_class_does_not_exist()
    {
        $factory = new RepositoryMockFactory();

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => 'UnknownClass',
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                    ]
                ]
            ]
        ]);

        $factory($container->reveal());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage Repository class ProophTest\EventStore\Mock\FaultyRepositoryMock must be a sub class of Prooph\EventStore\Aggregate\AggregateRepository
     */
    public function it_throws_exception_if_repository_class_is_not_a_subclass_of_aggregate_repository()
    {
        $factory = new RepositoryMockFactory();

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => FaultyRepositoryMock::class,
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                    ]
                ]
            ]
        ]);

        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_creates_repository_with_configured_stream_name_and_snapshot_store_if_given()
    {
        $factory = new RepositoryMockFactory();

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => RepositoryMock::class,
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                        'stream_name' => 'custom_stream_name',
                        'snapshot_store' => 'ultra_fast_snapshot_store',
                    ]
                ]
            ]
        ]);

        $container->get(EventStore::class)->willReturn($this->eventStore);

        $userTranslator = $this->prophesize(AggregateTranslator::class);

        $container->get('user_translator')->willReturn($userTranslator->reveal());

        $streamStrategy = $this->prophesize(StreamStrategy::class);

        $snapshotStore = $this->prophesize(SnapshotStore::class);
        $container->has('ultra_fast_snapshot_store')->willReturn(true);
        $container->get('ultra_fast_snapshot_store')->willReturn($snapshotStore->reveal());

        /** @var $repo RepositoryMock */
        $repo = $factory($container->reveal());

        $this->assertInstanceOf(RepositoryMock::class, $repo);

        $this->assertSame($this->eventStore, $repo->accessEventStore());
        $this->assertInstanceOf(AggregateType::class, $repo->accessAggregateType());
        $this->assertEquals(User::class, $repo->accessAggregateType()->toString());
        $this->assertSame($userTranslator->reveal(), $repo->accessAggregateTranslator());
        $this->assertSame('custom_stream_name', $repo->accessDeterminedStreamName()->toString());
        $this->assertSame($snapshotStore->reveal(), $repo->accessSnapshotStore());
        $this->assertFalse($repo->accessOneStreamPerAggregateFlag());
    }

    /**
     * @test
     */
    public function it_creates_repository_with_one_stream_per_aggregate_mode_enabled()
    {
        $factory = new RepositoryMockFactory();

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => RepositoryMock::class,
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                        'one_stream_per_aggregate' => true,
                    ]
                ]
            ]
        ]);

        $container->get(EventStore::class)->willReturn($this->eventStore);

        $userTranslator = $this->prophesize(AggregateTranslator::class);

        $container->get('user_translator')->willReturn($userTranslator->reveal());

        /** @var $repo RepositoryMock */
        $repo = $factory($container->reveal());

        $this->assertInstanceOf(RepositoryMock::class, $repo);

        $this->assertSame($this->eventStore, $repo->accessEventStore());
        $this->assertInstanceOf(AggregateType::class, $repo->accessAggregateType());
        $this->assertEquals(User::class, $repo->accessAggregateType()->toString());
        $this->assertSame($userTranslator->reveal(), $repo->accessAggregateTranslator());
        $this->assertEquals(User::class . '-' . '123', $repo->accessDeterminedStreamName('123')->toString());
        $this->assertNull($repo->accessSnapshotStore());
        $this->assertTrue($repo->accessOneStreamPerAggregateFlag());
    }
}
