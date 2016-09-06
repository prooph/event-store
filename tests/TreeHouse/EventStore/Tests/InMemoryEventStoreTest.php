<?php

namespace TreeHouse\EventStore\Tests;

use PHPUnit_Framework_TestCase;
use TreeHouse\EventStore\Event;
use TreeHouse\EventStore\EventStream;
use TreeHouse\EventStore\InMemoryEventStore;

class InMemoryEventStoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var InMemoryEventStore
     */
    private $eventStore;

    public function setUp()
    {
        $this->eventStore = new InMemoryEventStore();
    }

    /**
     * @test
     */
    public function it_appends_events()
    {
        $uuid = 'EC16EE75-5404-40D5-B96C-AAFEF7790DE8';

        $events = [
            $this->getEvent($version = 1, $uuid)->reveal(),
            $this->getEvent($version = 2, $uuid)->reveal(),
        ];

        $this->eventStore->append(new EventStream($events));

        $moreEvents = [
            $this->getEvent($version = 3, $uuid)->reveal(),
        ];

        $this->eventStore->append(new EventStream($moreEvents));

        $expectedEvents = array_merge($events, $moreEvents);

        $this->assertEquals(
            count($expectedEvents),
            $this->eventStore->getStream($uuid)->count()
        );
    }

    /**
     * @test
     * @expectedException \TreeHouse\EventStore\DuplicateVersionException
     */
    public function it_throws_when_version_is_duplicate()
    {
        $uuid = 'EC16EE75-5404-40D5-B96C-AAFEF7790DE8';

        $events = [
            $this->getEvent($version = 1, $uuid)->reveal(),
            $this->getEvent($version = 1, $uuid)->reveal(),
        ];

        $this->eventStore->append(new EventStream($events));
    }

    /**
     * @test
     * @expectedException \TreeHouse\EventStore\EventStreamNotFoundException
     */
    public function it_throws_when_stream_is_not_found()
    {
        $this->eventStore->getStream('unknown_stream_identifier');
    }

    /**
     * @param $version
     * @param $id
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getEvent($version, $id)
    {
        $event = $this->prophesize(Event::class);
        $event->getVersion()->willReturn($version);
        $event->getId()->willReturn($id);

        return $event;
    }
}
