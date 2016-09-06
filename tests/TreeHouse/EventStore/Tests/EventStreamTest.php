<?php

namespace TreeHouse\EventStore\Tests;

use Iterator;
use PHPUnit_Framework_TestCase;
use TreeHouse\EventStore\Event;
use TreeHouse\EventStore\EventStream;

class EventStreamTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->event = $this->prophesize(Event::class);

        $this->eventStream = new EventStream([
            $this->event->reveal(),
            $this->event->reveal(),
            $this->event->reveal(),
        ]);
    }

    /**
     * @test
     */
    public function it_is_countable()
    {
        $this->assertEquals(
            3,
            count($this->eventStream)
        );
    }

    /**
     * @test
     */
    public function it_is_iterable()
    {
        $this->assertInstanceOf(
            Iterator::class,
            $this->eventStream->getIterator()
        );
    }
}
