<?php

namespace TreeHouse\EventStore\Tests\Encryption;

use PHPUnit_Framework_TestCase;
use TreeHouse\EventStore\Encryption\CryptoEventStream;
use TreeHouse\EventStore\Encryption\EventCryptoInterface;
use TreeHouse\EventStore\Event;
use TreeHouse\EventStore\EventStream;
use TreeHouse\EventStore\EventStreamInterface;

class CryptoEventStreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_throws_exception_when_given_invalid_mode()
    {
        new CryptoEventStream(
            $this->prophesize(EventStreamInterface::class)->reveal(),
            [],
            'some-key',
            'wrong-mode'
        );
    }

    /**
     * @test
     */
    public function it_counts()
    {
        $innerStream = $this->prophesize(EventStreamInterface::class);
        $innerStream->count()->willReturn(99);

        $eventStream = new CryptoEventStream(
            $innerStream->reveal(),
            [],
            'some-key',
            CryptoEventStream::ENCRYPT
        );

        $this->assertEquals(99, $eventStream->count());
    }

    /**
     * @test
     */
    public function it_encrypts()
    {
        $event = new Event(
            'some-id',
            'name',
            'plain-payload',
            1,
            1
        );

        /** @var EventCryptoInterface $crypto */
        $crypto = $this->prophesize(EventCryptoInterface::class);
        $crypto->supports('name')->willReturn(true);
        $crypto->encrypt('plain-payload', 1, 'some-key')->willReturn('encrypted-payload');

        $eventStream = new CryptoEventStream(
            new EventStream([$event]),
            [$crypto->reveal()],
            'some-key',
            CryptoEventStream::ENCRYPT
        );

        list($encryptedEvent) = iterator_to_array($eventStream);

        $this->assertEquals('encrypted-payload', $encryptedEvent->getPayload());
    }
    /**
     * @test
     */
    public function it_decrypts()
    {
        $encryptedEvent = new Event(
            'some-id',
            'name',
            'encrypted-payload',
            1,
            1
        );

        /** @var EventCryptoInterface $crypto */
        $crypto = $this->prophesize(EventCryptoInterface::class);
        $crypto->supports('name')->willReturn(true);
        $crypto->decrypt('encrypted-payload', 1, 'some-key')->willReturn('plain-payload');

        $eventStream = new CryptoEventStream(
            new EventStream([$encryptedEvent]),
            [$crypto->reveal()],
            'some-key',
            CryptoEventStream::DECRYPT
        );

        list($event) = iterator_to_array($eventStream);

        $this->assertEquals('plain-payload', $event->getPayload());
    }
}
