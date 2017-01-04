<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Aggregate;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\AggregateTypeProvider;
use ProophTest\EventStore\Mock\Post;
use ProophTest\EventStore\Mock\User;

/**
 * Class AggregateTypeTest
 *
 * @package ProophTest\EventStore\Aggregate
 */
class AggregateTypeTest extends TestCase
{
    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\InvalidArgumentException
     */
    public function it_throws_exception_when_trying_to_create_from_string_as_aggregate_root()
    {
        AggregateType::fromAggregateRoot('invalid');
    }

    /**
     * @test
     */
    public function it_delegates_on_creating_from_aggregate_root_when_it_implements_aggregate_type_provider()
    {
        $aggregateRoot = $this->prophesize(AggregateTypeProvider::class);
        $aggregateRoot->aggregateType()->willReturn(AggregateType::fromString('stdClass'))->shouldBeCalled();

        $this->assertEquals('stdClass', AggregateType::fromAggregateRoot($aggregateRoot->reveal())->toString());
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\InvalidArgumentException
     * @expectedExceptionMessage Aggregate root class must be a string
     */
    public function it_throws_exception_on_creating_from_aggregate_root_class_when_no_string_given()
    {
        AggregateType::fromAggregateRootClass(666);
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\InvalidArgumentException
     * @expectedExceptionMessage Aggregate root class unknown_class can not be found
     */
    public function it_throws_exception_on_creating_from_aggregate_root_class_when_unknown_class_given()
    {
        AggregateType::fromAggregateRootClass('unknown_class');
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\InvalidArgumentException
     * @expectedExceptionMessage AggregateType must be a non empty string
     */
    public function it_throws_exception_when_trying_to_create_from_empty_string()
    {
        AggregateType::fromString(null);
    }

    /**
     * @test
     */
    public function it_asserts_correct_aggregate_type()
    {
        $aggregateType = AggregateType::fromAggregateRootClass(User::class);

        $aggregateRoot = $this->prophesize(AggregateTypeProvider::class);

        $aggregateRoot->aggregateType()->willReturn(AggregateType::fromAggregateRootClass(User::class));

        $aggregateType->assert($aggregateRoot->reveal());
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\AggregateTypeException
     * @expectedExceptionMessage Aggregate types must be equal. ProophTest\EventStore\Mock\User != ProophTest\EventStore\Mock\Post
     */
    public function it_throws_exception_if_type_is_not_correct()
    {
        $aggregateType = AggregateType::fromAggregateRootClass(User::class);

        $aggregateRoot = $this->prophesize(AggregateTypeProvider::class);

        $aggregateRoot->aggregateType()->willReturn(AggregateType::fromAggregateRootClass(Post::class));

        $aggregateType->assert($aggregateRoot->reveal());
    }

    /**
     * @test
     */
    public function it_delegates_to_string()
    {
        $type = AggregateType::fromAggregateRootClass('stdClass');
        $this->assertEquals('stdClass', (string) $type);
    }
}
