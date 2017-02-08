<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
        $message1->metadata()->willReturn([])->shouldBeCalled();
        $message1->withAddedMetadata('key', 'value')->willReturn($upcastedMessage1)->shouldBeCalled();
        $message1->withAddedMetadata('key', 'another_value')->willReturn($upcastedMessage2)->shouldBeCalled();
        $message1 = $message1->reveal();

        $message2 = $this->prophesize(Message::class);
        $message2->metadata()->willReturn(['foo' => 'baz'])->shouldBeCalled();
        $message2 = $message2->reveal();

        $message3 = $this->prophesize(Message::class);
        $message3->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $message3->withAddedMetadata('key', 'value')->shouldNotBeCalled();
        $message3->withAddedMetadata('key', 'another_value')->shouldNotBeCalled();
        $message3 = $message3->reveal();

        $iterator = new \ArrayIterator([
            $message1,
            $message2,
            $message3,
        ]);

        $upcastingIterator = new UpcastingIterator($this->createUpcaster(), $iterator);

        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertTrue($upcastingIterator->valid());
        $this->assertSame($upcastedMessage1, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertSame($upcastedMessage2, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertSame($message3, $upcastingIterator->current());
        $upcastingIterator->rewind();
        $this->assertEquals(0, $upcastingIterator->key());
        $this->assertTrue($upcastingIterator->valid());
        $this->assertSame($upcastedMessage1, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertSame($upcastedMessage2, $upcastingIterator->current());
        $upcastingIterator->next();
        $this->assertSame($message3, $upcastingIterator->current());
    }

    protected function createUpcaster(): SingleEventUpcaster
    {
        return new class extends SingleEventUpcaster {
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
