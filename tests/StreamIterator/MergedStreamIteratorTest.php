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
            new InMemoryStreamIterator([
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 5], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388510')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 7], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388519')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 8], 3, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388520')),
            ]),
            new InMemoryStreamIterator([
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 1], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388501')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 2], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388503')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 4], 3, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388509')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 6], 4, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388515')),
            ]),
            new InMemoryStreamIterator([
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 0], 1, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388500')),
                TestDomainEvent::withPayloadAndSpecifiedCreatedAt(['expected_index' => 3], 2, DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', '2019-05-10T10:18:19.388503')),
            ]),
        ];
    }

    /**
     * test
     */
    public function it_implements_stream_iterator(): void
    {
        $iterator = new MergedStreamIterator(...$this->getStreams());

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    /**
     * test
     */
    public function it_counts_correct(): void
    {
        $iterator = new MergedStreamIterator(...$this->getStreams());

        $this->assertEquals(8, $iterator->count());
    }

    /**
     * @test
     */
    public function it_can_rewind(): void
    {
        $iterator = new MergedStreamIterator(...$this->getStreams());

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
        $iterator = new MergedStreamIterator(...$this->getStreams());

        foreach ($iterator as $key => $message) {
            $this->assertEquals($key, $message->payload()['expected_index']);
        }
    }
}
