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

namespace ProophTest\EventStore\StreamIterator;

use Prooph\EventStore\StreamIterator\EmptyStreamIterator;
use Prooph\EventStore\StreamIterator\StreamIterator;

class EmptyStreamIteratorTest extends AbstractStreamIteratorTest
{
    /**
     * @test
     */
    public function it_implements_stream_iterator(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertInstanceOf(StreamIterator::class, $iterator);
    }

    /**
     * @test
     */
    public function it_implements_empty_iterator(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertInstanceOf(\EmptyIterator::class, $iterator);
    }

    /**
     * @test
     */
    public function it_counts_correct(): void
    {
        $iterator = new EmptyStreamIterator();

        $this->assertEquals(0, $iterator->count());
    }
}
