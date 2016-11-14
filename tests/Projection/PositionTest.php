<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Projection;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Projection\Position;

class PositionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_position(): void
    {
        $position = new Position(['foo' => 0, 'bar' => 0]);

        $this->assertEquals(['foo' => 0, 'bar' => 0], $position->streamPositions());
    }

    /**
     * @test
     */
    public function it_increases_and_resets_position(): void
    {
        $position = new Position(['foo' => 0, 'bar' => 0]);
        $position->inc('foo');
        $position->inc('foo');
        $position->inc('foo');
        $position->inc('bar');

        $this->assertEquals(3, $position->pos('foo'));
        $this->assertEquals(1, $position->pos('bar'));
        $this->assertEquals(['foo' => 3, 'bar' => 1], $position->streamPositions());

        $position->reset();

        $this->assertEquals(['foo' => 0, 'bar' => 0], $position->streamPositions());
    }

    /**
     * @test
     */
    public function it__merges_position(): void
    {
        $position = new Position(['foo' => 0, 'bar' => 0]);

        $position->merge(['foo' => 0, 'bar' => 1, 'bag' => 0]);

        $this->assertEquals(['foo' => 0, 'bar' => 1, 'bag' => 0], $position->streamPositions());
    }

    /**
     * @test
     */
    public function it_throws_exception_trying_to_increment_unknown_stream_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $position = new Position(['foo' => 0, 'bar' => 0]);
        $position->inc('unknown');
    }

    /**
     * @test
     */
    public function it_throws_exception_trying_to_ask_for_unkown_stream_name_pos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $position = new Position(['foo' => 0, 'bar' => 0]);
        $position->pos('unknown');
    }
}
