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

namespace ProophTest\EventStore\Util;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Util\ArrayCache;

class ArrayCacheTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_when_invalid_size_given(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ArrayCache(-1);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_too_high_position_given(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cache = new ArrayCache(100);

        $cache->get(101);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_too_low_position_given(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cache = new ArrayCache(100);

        $cache->get(-1);
    }

    /**
     * @test
     */
    public function it_gets_checks_for_values(): void
    {
        $cache = new ArrayCache(4);

        $this->assertNull($cache->get(0));

        $cache->append(1);
        $cache->append(2);
        $cache->append(3);
        $cache->append(4);

        $this->assertTrue($cache->has(4));
        $this->assertEquals(3, $cache->get(2));

        $cache->append(5);
        $cache->append(6);
        $cache->append(7);

        $this->assertTrue($cache->has(7));
        $this->assertEquals(7, $cache->get(2));
        $this->assertEquals(4, $cache->get(3));
    }
}
