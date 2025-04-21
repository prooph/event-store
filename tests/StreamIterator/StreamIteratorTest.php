<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2025 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2025 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\StreamIterator;

use Countable;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\StreamIterator\StreamIterator;

class StreamIteratorTest extends TestCase
{
    #[Test]
    public function it_implements_iterator(): void
    {
        $this->assertInstanceOf(Iterator::class, $this->getStreamIteratorMock());
    }

    #[Test]
    public function it_implements_countable(): void
    {
        $this->assertInstanceOf(Countable::class, $this->getStreamIteratorMock());
    }

    private function getStreamIteratorMock(): StreamIterator
    {
        return new class() implements StreamIterator {
            public function count(): int
            {
                return 0;
            }

            public function current(): mixed
            {
                return null;
            }

            public function key(): mixed
            {
                return null;
            }

            public function next(): void
            {
            }

            public function rewind(): void
            {
            }

            public function valid(): bool
            {
                return false;
            }
        };
    }
}
