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
use Prooph\EventStore\Exception\OutOfRangeException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;

/**
 * Common tests for all projection manager implementations
 */
abstract class AbstractProjectionManagerTest extends TestCase
{
    /**
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @test
     */
    public function it_fetches_projection_names(): void
    {
        $projections = [];

        try {
            for ($i = 0; $i < 50; $i++) {
                $projection = $this->projectionManager->createProjection('user-' . $i);
                $projection->fromAll()->whenAny(function (): void {
                })->run(false);
                $projections[] = $projection;
            }

            for ($i = 0; $i < 20; $i++) {
                $projection = $this->projectionManager->createProjection(uniqid('rand'));
                $projection->fromAll()->whenAny(function (): void {
                })->run(false);
                $projections[] = $projection;
            }

            $this->assertCount(20, $this->projectionManager->fetchProjectionNames(null));
            $this->assertCount(70, $this->projectionManager->fetchProjectionNames(null, 200, 0));
            $this->assertCount(0, $this->projectionManager->fetchProjectionNames(null, 200, 100));
            $this->assertCount(10, $this->projectionManager->fetchProjectionNames(null, 10, 0));
            $this->assertCount(10, $this->projectionManager->fetchProjectionNames(null, 10, 10));
            $this->assertCount(5, $this->projectionManager->fetchProjectionNames(null, 10, 65));

            for ($i = 0; $i < 20; $i++) {
                $this->assertStringStartsWith('rand', $this->projectionManager->fetchProjectionNames(null, 1, $i)[0]);
            }

            $this->assertCount(30, $this->projectionManager->fetchProjectionNamesRegex('ser-', 30, 0));
            $this->assertCount(0, $this->projectionManager->fetchProjectionNamesRegex('n-', 30, 0));
        } finally {
            foreach ($projections as $projection) {
                $projection->delete(false);
            }
        }
    }

    /**
     * @test
     */
    public function it_fetches_projection_names_with_filter(): void
    {
        $projection = $this->projectionManager->createProjection('user-1');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-2');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('rand-1');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-3');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertSame(['user-1'], $this->projectionManager->fetchProjectionNames('user-1'));
        $this->assertSame(['user-2'], $this->projectionManager->fetchProjectionNames('user-2', 2));
        $this->assertSame(['rand-1'], $this->projectionManager->fetchProjectionNames('rand-1', 5));

        $this->assertSame([], $this->projectionManager->fetchProjectionNames('foo'));
        $this->assertSame([], $this->projectionManager->fetchProjectionNames('foo', 5));
        $this->assertSame([], $this->projectionManager->fetchProjectionNames('foo', 10, 100));
    }

    /**
     * @test
     */
    public function it_fetches_projection_names_sorted(): void
    {
        $projection = $this->projectionManager->createProjection('user-100');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-21');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('rand-5');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-10');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-1');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertEquals(
            ['rand-5', 'user-1', 'user-10', 'user-100', 'user-21'],
            $this->projectionManager->fetchProjectionNames(null)
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_invalid_limit(): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid limit "-1" given. Must be greater than 0.');

        $this->projectionManager->fetchProjectionNames(null, -1, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_invalid_offset(): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid offset "-1" given. Must be greater or equal than 0.');

        $this->projectionManager->fetchProjectionNames(null, 1, -1);
    }

    /**
     * @test
     */
    public function it_fetches_projection_names_using_regex(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $projection = $this->projectionManager->createProjection('user-' . $i);
            $projection->fromAll()->whenAny(function (): void {
            })->run(false);
        }

        for ($i = 0; $i < 20; $i++) {
            $projection = $this->projectionManager->createProjection(uniqid('rand'));
            $projection->fromAll()->whenAny(function (): void {
            })->run(false);
        }

        $this->assertCount(20, $this->projectionManager->fetchProjectionNamesRegex('user'));
        $this->assertCount(50, $this->projectionManager->fetchProjectionNamesRegex('user', 100));
        $this->assertCount(30, $this->projectionManager->fetchProjectionNamesRegex('ser-', 30, 0));
        $this->assertCount(0, $this->projectionManager->fetchProjectionNamesRegex('n-', 30, 0));
        $this->assertCount(5, $this->projectionManager->fetchProjectionNamesRegex('rand', 100, 15));
    }

    /**
     * @test
     */
    public function it_fetches_projection_names_sorted_using_regex(): void
    {
        $projection = $this->projectionManager->createProjection('user-100');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-21');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('rand-5');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-10');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $projection = $this->projectionManager->createProjection('user-1');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertEquals(
            json_encode(['user-1', 'user-10', 'user-100', 'user-21']),
            json_encode($this->projectionManager->fetchProjectionNamesRegex('ser-'))
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_regex_with_invalid_limit(): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid limit "-1" given. Must be greater than 0.');

        $this->projectionManager->fetchProjectionNamesRegex('foo', -1, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_regex_with_invalid_offset(): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid offset "-1" given. Must be greater or equal than 0.');

        $this->projectionManager->fetchProjectionNamesRegex('bar', 1, -1);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->projectionManager->fetchProjectionNamesRegex('invalid)', 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_status(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionStatus('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_stream_positions(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionStreamPositions('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_state(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionState('unkown');
    }

    /**
     * @test
     */
    public function it_fetches_projection_status(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertSame(ProjectionStatus::IDLE(), $this->projectionManager->fetchProjectionStatus('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_stream_positions(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertSame([], $this->projectionManager->fetchProjectionStreamPositions('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_state(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');
        $projection->fromAll()->whenAny(function (): void {
        })->run(false);

        $this->assertSame([], $this->projectionManager->fetchProjectionState('test-projection'));
    }
}
