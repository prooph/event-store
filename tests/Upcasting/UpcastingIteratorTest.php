<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Upcasting;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Upcasting\SingleEventUpcaster;
use Prooph\EventStore\Upcasting\UpcastingIterator;

class UpcastingIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_iterates(): void
    {
        $upcastedMessage1 = $this->prophesize(Message::class);
        $upcastedMessage1 = $upcastedMessage1->reveal();

        $upcastedMessage2 = $this->prophesize(Message::class);
        $upcastedMessage2 = $upcastedMessage2->reveal();

        $message1 = $this->prophesize(Message::class);
        $message1->metadata()->willReturn(['foo' => 'baz'])->shouldBeCalled();
        $message1 = $message1->reveal();

        $message2 = $this->prophesize(Message::class);
        $message2->metadata()->willReturn([])->shouldBeCalled();
        $message2->withAddedMetadata('key', 'value')->willReturn($upcastedMessage1)->shouldBeCalled();
        $message2->withAddedMetadata('key', 'another_value')->willReturn($upcastedMessage2)->shouldBeCalled();
        $message2 = $message2->reveal();

        $message3 = $this->prophesize(Message::class);
        $message3->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $message3->withAddedMetadata('key', 'value')->shouldNotBeCalled();
        $message3->withAddedMetadata('key', 'another_value')->shouldNotBeCalled();
        $message3 = $message3->reveal();

        $message4 = $this->prophesize(Message::class);
        $message4->metadata()->willReturn(['foo' => 'baz'])->shouldBeCalled();
        $message4 = $message4->reveal();

        $iterator = new \ArrayIterator([
            $message1,
            $message2,
            $message3,
            $message4,
        ]);

        $upcastingIterator = new UpcastingIterator($this->createUpcaster(), $iterator);

        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertTrue($upcastingIterator->valid());
        $this->assertSame($upcastedMessage1, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertEquals(1, $upcastingIterator->key());
        $this->assertSame($upcastedMessage2, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertEquals(2, $upcastingIterator->key());
        $this->assertSame($message3, $upcastingIterator->current());
        $upcastingIterator->rewind();
        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertTrue($upcastingIterator->valid());
        $this->assertSame($upcastedMessage1, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertEquals(1, $upcastingIterator->key());
        $this->assertSame($upcastedMessage2, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertEquals(2, $upcastingIterator->key());
        $this->assertSame($message3, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertFalse($upcastingIterator->valid());
        $this->assertNull($upcastingIterator->current());
    }

    /**
     * @test
     */
    public function it_iterates_on_iterator_with_removed_messages_only(): void
    {
        $message = $this->prophesize(Message::class);
        $message->metadata()->willReturn(['foo' => 'baz'])->shouldBeCalled();
        $message = $message->reveal();

        $iterator = new \ArrayIterator([$message]);

        $upcastingIterator = new UpcastingIterator($this->createUpcaster(), $iterator);

        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertFalse($upcastingIterator->valid());
        $this->assertNull($upcastingIterator->current());
    }

    /**
     * @test
     */
    public function it_iterates_over_array_iterator(): void
    {
        $iterator = new \ArrayIterator();

        $upcastingIterator = new UpcastingIterator($this->createUpcaster(), $iterator);

        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertFalse($upcastingIterator->valid());
        $this->assertNull($upcastingIterator->current());
    }

    /**
     * @test
     */
    public function it_iterates_over_empty_iterator(): void
    {
        $iterator = new \EmptyIterator();

        $upcastingIterator = new UpcastingIterator($this->createUpcaster(), $iterator);

        $this->assertFalse($upcastingIterator->valid());
        $this->assertNull($upcastingIterator->current());
    }

    protected function createUpcaster(): SingleEventUpcaster
    {
        return new class() extends SingleEventUpcaster {
            protected function canUpcast(Message $message): bool
            {
                return $message->metadata() !== ['foo' => 'bar'];
            }

            protected function doUpcast(Message $message): array
            {
                if ($message->metadata() === ['foo' => 'baz']) {
                    return [];
                }

                return [$message->withAddedMetadata('key', 'value'), $message->withAddedMetadata('key', 'another_value')];
            }
        };
    }
}
