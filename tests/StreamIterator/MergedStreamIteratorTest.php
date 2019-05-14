<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\StreamIterator;

use DateTimeImmutable;
use Prooph\EventStore\StreamIterator\InMemoryStreamIterator;
use Prooph\EventStore\StreamIterator\MergedStreamIterator;
use Prooph\EventStore\StreamIterator\StreamIterator;
use ProophTest\EventStore\Mock\TestDomainEvent;

class MergedStreamIteratorTest extends AbstractStreamIteratorTest
{
    public function getStreams(): array
    {
        return [
            'streamA' => new InMemoryStreamIterator([
                1 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 5, 'expected_position' => 1, 'expected_stream_name' => 'streamA'], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388510')),
                2 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 7, 'expected_position' => 2, 'expected_stream_name' => 'streamA'], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388519')),
                3 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 8, 'expected_position' => 3, 'expected_stream_name' => 'streamA'], 3, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388520')),
            ]),
            'streamB' => new InMemoryStreamIterator([
                1 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 1, 'expected_position' => 1, 'expected_stream_name' => 'streamB'], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388501')),
                2 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 2, 'expected_position' => 2, 'expected_stream_name' => 'streamB'], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388503')),
                3 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 4, 'expected_position' => 3, 'expected_stream_name' => 'streamB'], 3, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388509')),
                4 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 6, 'expected_position' => 4, 'expected_stream_name' => 'streamB'], 4, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388515')),
            ]),
            'streamC' => new InMemoryStreamIterator([
                1 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 0, 'expected_position' => 1, 'expected_stream_name' => 'streamC'], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388500')),
                2 => TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 3, 'expected_position' => 2, 'expected_stream_name' => 'streamC'], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388503')),
            ]),
        ];
    }

    /**
     * @test
     */
    public function it_implements_stream_iterator(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    /**
     * @test
     */
    public function it_counts_correct(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        $this->assertEquals(9, $iterator->count());
    }

    /**
     * @test
     */
    public function it_can_rewind(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        $iterator->next();

        $iterator->rewind();

        $message = $iterator->current();

        $this->assertEquals(0, $message->payload()['expected_index']);
    }

    /**
     * @test
     */
    public function it_returns_messages_in_order(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        $index = 0;
        foreach ($iterator as $position => $message) {
            $this->assertEquals($index, $message->payload()['expected_index']);
            $this->assertEquals($iterator->streamName(), $message->payload()['expected_stream_name']);

            $index++;
        }
    }

    /**
     * @test
     */
    public function it_returns_correct_stream_name(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        foreach ($iterator as $position => $message) {
            $this->assertEquals($iterator->streamName(), $message->payload()['expected_stream_name']);
        }
    }

    /**
     * @test
     */
    public function key_represents_event_position(): void
    {
        $iterator = new MergedStreamIterator(\array_keys($this->getStreams()), ...\array_values($this->getStreams()));

        foreach ($iterator as $position => $message) {
            $this->assertEquals($position, $message->payload()['expected_position']);
        }
    }
}
