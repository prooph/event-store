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

use ArrayIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\StreamIterator\InMemoryStreamIterator;
use Prooph\EventStore\StreamIterator\StreamIterator;

class InMemoryStreamIteratorTest extends TestCase
{
    #[Test]
    public function it_implements_stream_iterator(): void
    {
        $iterator = new InMemoryStreamIterator();

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    #[Test]
    public function it_implements_array_iterator(): void
    {
        $iterator = new InMemoryStreamIterator();

        $this->assertInstanceOf(ArrayIterator::class, $iterator);
    }
}
