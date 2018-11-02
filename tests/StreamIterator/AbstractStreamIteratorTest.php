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

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\StreamIterator\StreamIterator;

abstract class AbstractStreamIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_iterator(): void
    {
        $iterator = $this->getMockBuilder(StreamIterator::class)->getMock();

        $this->assertInstanceOf(\Iterator::class, $iterator);
    }

    /**
     * @test
     */
    public function it_implements_countable(): void
    {
        $iterator = $this->getMockBuilder(StreamIterator::class)->getMock();

        $this->assertInstanceOf(\Countable::class, $iterator);
    }
}
