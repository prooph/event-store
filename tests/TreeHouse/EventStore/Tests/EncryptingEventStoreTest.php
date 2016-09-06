<?php

namespace TreeHouse\EventStore\Tests;

use PHPUnit_Framework_TestCase;
use TreeHouse\EventStore\EncryptingEventStore;
use TreeHouse\EventStore\Encryption\CryptoEventStream;
use TreeHouse\EventStore\Encryption\CryptoEventStreamFactory;
use TreeHouse\EventStore\EventStoreInterface;
use TreeHouse\EventStore\EventStream;

class EncryptingEventStoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_wraps_stream_with_descrypted_stream_from_factory()
    {
        $eventStream = new EventStream([]);

        $innerStore = $this->prophesize(EventStoreInterface::class);
        $innerStore->getStream('some-id')->willReturn($eventStream);

        $factory = $this->prophesize(CryptoEventStreamFactory::class);
        $factory->createDecryptingStream($eventStream)->shouldBeCalled();

        $store = new EncryptingEventStore(
            $innerStore->reveal(),
            $factory->reveal()
        );

        $store->getStream('some-id');
    }

    /**
     * @test
     */
    public function it_wraps_stream_with_encrypted_stream_from_factory()
    {
        $eventStream = new EventStream([]);
        $cryptoEventStream = $this->prophesize(CryptoEventStream::class)->reveal();

        $innerStore = $this->prophesize(EventStoreInterface::class);
        $innerStore->append($cryptoEventStream)->shouldBeCalled();

        $factory = $this->prophesize(CryptoEventStreamFactory::class);
        $factory->createEncryptingStream($eventStream)
            ->willReturn($cryptoEventStream);

        $store = new EncryptingEventStore(
            $innerStore->reveal(),
            $factory->reveal()
        );

        $store->append($eventStream);
    }
}
