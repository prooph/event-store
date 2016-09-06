<?php

namespace TreeHouse\EventStore\Tests;

use DateTime;
use PHPUnit_Framework_TestCase;
use TreeHouse\EventStore\Event;

class EventTest extends PHPUnit_Framework_TestCase
{
    const UUID = 'B26B5343-9756-4031-8923-E28B8606D3C9';
    const NAME = 'EventName';
    const PAYLOAD = '{payload:json}';
    const PAYLOAD_VERSION = 1;
    const VERSION = 1;
    const DATE = '2012-01-01';

    /**
     * @var Event
     */
    private $event;

    public function setUp()
    {
        $this->event = new Event(
            self::UUID,
            self::NAME,
            self::PAYLOAD,
            self::PAYLOAD_VERSION,
            self::VERSION,
            new DateTime(self::DATE)
        );
    }

    /**
     * @test
     */
    public function it_exposes_id()
    {
        $this->assertEquals(
            $this->event->getId(),
            self::UUID
        );
    }

    /**
     * @test
     */
    public function it_exposes_name()
    {
        $this->assertEquals(
            $this->event->getName(),
            self::NAME
        );
    }

    /**
     * @test
     */
    public function it_exposes_payload()
    {
        $this->assertEquals(
            $this->event->getPayload(),
            self::PAYLOAD
        );
    }

    /**
     * @test
     */
    public function it_exposes_version()
    {
        $this->assertEquals(
            $this->event->getVersion(),
            self::VERSION
        );
    }

    /**
     * @test
     */
    public function it_exposes_date()
    {
        $date = $this->event->getDate();

        $this->assertEquals(
            $date->format('Y-m-d'),
            self::DATE
        );
    }
}
