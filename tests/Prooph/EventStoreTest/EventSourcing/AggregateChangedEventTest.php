<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 21:45
 */

namespace Prooph\EventStoreTest\EventSourcing;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Prooph\EventStoreTest\TestCase;

/**
 * Class AggregateChangedEventTest
 *
 * @package Prooph\EventStoreTest\EventSourcing
 * @author Alexander Miertsch <contact@prooph.de>
 */
class AggregateChangedEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_new_uuid_after_construct()
    {
        $event = new AggregateChangedEvent(1, array());

        $this->assertInstanceOf('Rhumsaa\Uuid\Uuid', $event->uuid());
    }

    /**
     * @test
     */
    public function it_references_an_aggregate()
    {
        $event = new AggregateChangedEvent(1, array());

        $this->assertEquals(1, $event->aggregateId());
    }

    /**
     * @test
     */
    public function it_has_an_occurred_on_datetime_after_construct()
    {
        $event = new AggregateChangedEvent(1, array());

        $this->assertInstanceOf('ValueObjects\DateTime\DateTime', $event->occurredOn());
    }

    /**
     * @test
     */
    public function it_has_assigned_payload_after_construct()
    {
        $payload = array('test payload');

        $event = new AggregateChangedEvent(1, $payload);

        $this->assertEquals($payload, $event->payload());
    }

    /**
     * @test
     */
    public function it_returns_an_array_reader_with_the_populated_payload()
    {
        $event = new AggregateChangedEvent(1, array('key' => 'value'));

        $this->assertEquals('value', $event->toPayloadReader()->stringValue('key'));
    }
}
 