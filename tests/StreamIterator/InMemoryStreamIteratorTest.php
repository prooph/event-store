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

namespace ProophTest\EventStore\StreamIterator;

use Prooph\EventStore\StreamIterator\InMemoryStreamIterator;
use Prooph\EventStore\StreamIterator\StreamIterator;

class InMemoryStreamIteratorTest extends AbstractStreamIteratorTest
{
    /**
     * @test
     */
    public function it_implements_stream_iterator(): void
    {
        $iterator = new InMemoryStreamIterator();

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    /**
     * @test
     */
    public function it_implements_array_iterator(): void
    {
        $iterator = new InMemoryStreamIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
    }
}
