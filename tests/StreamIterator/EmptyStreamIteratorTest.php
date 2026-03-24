<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2026 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2026 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\StreamIterator;

use EmptyIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\StreamIterator\EmptyStreamIterator;
use Prooph\EventStore\StreamIterator\StreamIterator;

class EmptyStreamIteratorTest extends TestCase
{
    #[Test]
    public function it_implements_stream_iterator(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    #[Test]
    public function it_implements_empty_iterator(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertInstanceOf(EmptyIterator::class, $iterator);
    }

    #[Test]
    public function it_counts_correct(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertEquals(0, $iterator->count());
    }
}
