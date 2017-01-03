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

namespace ProophTest\EventStore\Projection;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Projection\ProjectionOptions;

class ProjectionOptionsTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_when_cache_size_option_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProjectionOptions::fromArray([]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_persist_blocksize_option_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProjectionOptions::fromArray(['cache_size' => 1]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_sleep_option_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProjectionOptions::fromArray(['cache_size' => 1, 'persist_block_size' => 1]);
    }

    /**
     * @test
     */
    public function it_creates_instance(): void
    {
        $options = ProjectionOptions::fromArray([
            'cache_size' => 5,
            'persist_block_size' => 15,
            'sleep' => 2500000,
        ]);

        $this->assertEquals(5, $options->cacheSize());
        $this->assertEquals(15, $options->persistBlockSize());
        $this->assertEquals(2500000, $options->sleep());
    }
}
