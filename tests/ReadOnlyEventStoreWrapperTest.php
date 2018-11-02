<?php

/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\ReadOnlyEventStoreWrapper;
use Prooph\EventStore\StreamName;
use Prophecy\Argument;

class ReadOnlyEventStoreWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function it_delegates_method_calls_to_internal_event_store(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->fetchStreamMetadata(Argument::type(StreamName::class))->willReturn([])->shouldBeCalled();
        $eventStore->hasStream(Argument::type(StreamName::class))->willReturn(true)->shouldBeCalled();
        $eventStore->load(Argument::type(StreamName::class), 0, 10, null)->willReturn(new \ArrayIterator())->shouldBeCalled();
        $eventStore->loadReverse(Argument::type(StreamName::class), 0, 10, null)->willReturn(new \ArrayIterator())->shouldBeCalled();
        $eventStore->fetchStreamNames('foo', null, 0, 10)->willReturn(['foobar', 'foobaz'])->shouldBeCalled();
        $eventStore->fetchStreamNamesRegex('^foo', null, 0, 10)->willReturn(['foobar', 'foobaz'])->shouldBeCalled();
        $eventStore->fetchCategoryNames('foo', 0, 10)->willReturn(['foo-1', 'foo-2'])->shouldBeCalled();
        $eventStore->fetchCategoryNamesRegex('^foo', 0, 10)->willReturn(['foo-1', 'foo-2'])->shouldBeCalled();

        $testStream = new StreamName('foo');

        $readOnlyEventStore = new ReadOnlyEventStoreWrapper($eventStore->reveal());

        $this->assertEmpty($readOnlyEventStore->fetchStreamMetadata($testStream));
        $this->assertTrue($readOnlyEventStore->hasStream($testStream));
        $this->assertInstanceOf(\ArrayIterator::class, $readOnlyEventStore->load($testStream, 0, 10));
        $this->assertInstanceOf(\ArrayIterator::class, $readOnlyEventStore->loadReverse($testStream, 0, 10));
        $this->assertSame(['foobar', 'foobaz'], $readOnlyEventStore->fetchStreamNames('foo', null, 0, 10));
        $this->assertSame(['foobar', 'foobaz'], $readOnlyEventStore->fetchStreamNamesRegex('^foo', null, 0, 10));
        $this->assertSame(['foo-1', 'foo-2'], $readOnlyEventStore->fetchCategoryNames('foo', 0, 10));
        $this->assertSame(['foo-1', 'foo-2'], $readOnlyEventStore->fetchCategoryNamesRegex('^foo', 0, 10));
    }
}
