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

namespace ProophTest\EventStore\Projection;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

/**
 * Common tests for all event store projector implementations
 */
abstract class AbstractEventStoreProjectorTest extends TestCase
{
    /**
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @test
     */
    public function it_can_project_from_stream_and_reset(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;
        $projectionManager = $this->projectionManager;
        $projection = $projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use ($projectionManager, $testCase): array {
                    $testCase->assertSame(ProjectionStatus::RUNNING(), $projectionManager->fetchProjectionStatus('test_projection'));
                    $state['count']++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(49, $projection->getState()['count']);

        $projection->reset();

        $projection->run(false);

        $projection->run(false);

        $this->assertEquals(49, $projection->getState()['count']);

        $this->assertEquals($projection->getState(), $projectionManager->fetchProjectionState('test_projection'));

        $this->assertEquals(
            [
                'user-123' => 50,
            ],
            $projectionManager->fetchProjectionStreamPositions('test_projection')
        );
    }

    /**
     * @test
     */
    public function it_can_be_stopped_while_processing(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->whenAny(function (array $state, Message $event): array {
                $state['count']++;

                if ($state['count'] === 10) {
                    $this->stop();
                }

                return $state;
            })
            ->run(false);

        $this->assertEquals(10, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_streams(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStreams('user-123', 'user-234')
            ->whenAny(
                function (array $state, Message $event): array {
                    $state['count']++;

                    return $state;
                }
            )
            ->run(false);

        $this->assertEquals(100, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_all_ignoring_internal_streams(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');
        $this->prepareEventStream('$iternal-345');

        $testCase = $this;

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromAll()
            ->whenAny(
                function (array $state, Message $event) use ($testCase): array {
                    $state['count']++;
                    if ($state['count'] < 51) {
                        $testCase->assertEquals('user-123', $this->streamName());
                    } else {
                        $testCase->assertEquals('user-234', $this->streamName());
                    }

                    return $state;
                }
            )
            ->run(false);

        $this->assertEquals(100, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_category_with_when_any(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromCategory('user')
            ->whenAny(
                function (array $state, Message $event): array {
                    $state['count']++;

                    return $state;
                }
            )
            ->run(false);

        $this->assertEquals(100, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_categories_with_when(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');
        $this->prepareEventStream('guest-345');
        $this->prepareEventStream('guest-456');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromCategories('user', 'guest')
            ->when([
                UserCreated::class => function (array $state, Message $event): array {
                    $state['count']++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(4, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_resumes_projection_from_position(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStreams('user-123', 'user-234')
            ->when([
                UsernameChanged::class => function (array $state, Message $event): array {
                    $state['count']++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(49, $projection->getState()['count']);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->prepareEventStream('user-234');

        $projection->run(false);

        $this->assertEquals(148, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_ignores_error_on_delete_of_not_created_stream_projections(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event): array {
                    $this->stop();

                    return $state;
                },
            ])
            ->run();

        $projection->delete(true);

        $this->assertEmpty($this->projectionManager->fetchProjectionNames('test-projection'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_run_two_projections_at_the_same_time(): void
    {
        $this->expectException(\Prooph\EventStore\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Another projection process is already running');

        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projectionManager = $this->projectionManager;

        $projection
            ->fromStream('user-123')
            ->whenAny(
                function (array $state, Message $event) use ($projectionManager): array {
                    $projection = $projectionManager->createProjection('test_projection');

                    $projection
                        ->fromStream('user-123')
                        ->whenAny(
                            function (array $state, Message $event): array {
                                $this->linkTo('foo', $event);

                                return $state;
                            }
                        )
                        ->run();
                }
            )
            ->run();
    }

    /**
     * @test
     */
    public function it_deletes_projection_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createProjection('test_projection', [
            Projector::OPTION_PERSIST_BLOCK_SIZE => 10,
        ]);

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes): array {
                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->projectionManager->deleteProjection('test_projection', false);

        $projection->run(false);

        $this->assertEquals(0, $projection->getState()['count']);
        $this->assertEquals(49, $calledTimes);
    }

    /**
     * @test
     */
    public function it_deletes_projection_incl_emitting_events_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createProjection('test_projection', [
            Projector::OPTION_PERSIST_BLOCK_SIZE => 10,
        ]);

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes): array {
                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->projectionManager->deleteProjection('test_projection', true);

        $projection->run(false);

        $this->assertEquals(0, $projection->getState()['count']);
        $this->assertEquals(49, $calledTimes);
    }

    /**
     * @test
     */
    public function it_deletes_projection_during_run_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projectionManager = $this->projectionManager;

        $projection = $this->projectionManager->createProjection('test_projection', [
            Projector::OPTION_PERSIST_BLOCK_SIZE => 5,
        ]);

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes, $projectionManager): array {
                    static $wasReset = false;

                    if (! $wasReset) {
                        $projectionManager->deleteProjection('test_projection', false);
                        $wasReset = true;
                    }

                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(0, $projection->getState()['count']);
        $this->assertEquals(5, $calledTimes);
        $this->assertEquals([], $projectionManager->fetchProjectionNames('test_projection'));
    }

    /**
     * @test
     */
    public function it_deletes_projection_incl_emitted_events_during_run_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projectionManager = $this->projectionManager;

        $projection = $this->projectionManager->createProjection('test_projection', [
            Projector::OPTION_PERSIST_BLOCK_SIZE => 5,
        ]);

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes, $projectionManager): array {
                    static $wasReset = false;

                    if (! $wasReset) {
                        $projectionManager->deleteProjection('test_projection', true);
                        $wasReset = true;
                    }

                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(0, $projection->getState()['count']);
        $this->assertEquals(5, $calledTimes);
        $this->assertEquals([], $projectionManager->fetchProjectionNames('test_projection'));
    }

    /**
     * @test
     */
    public function it_resets_projection_before_start_when_it_was_reset_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes): array {
                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->projectionManager->resetProjection('test_projection');

        $projection->run(false);

        $this->assertEquals(99, $projection->getState()['count']);
        $this->assertEquals(148, $calledTimes);
    }

    /**
     * @test
     */
    public function it_resets_projection_during_run_when_it_was_reset_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projectionManager = $this->projectionManager;

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes, $projectionManager): array {
                    static $wasReset = false;

                    if (! $wasReset) {
                        $projectionManager->resetProjection('test_projection');
                        $wasReset = true;
                    }

                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $projection->run(false);

        $this->assertEquals(49, $projection->getState()['count']);
        $this->assertEquals(98, $calledTimes);
    }

    /**
     * @test
     */
    public function it_stops_when_projection_before_start_when_it_was_stopped_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes): array {
                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->projectionManager->stopProjection('test_projection');

        $projection->run(false);

        $this->assertEquals(49, $projection->getState()['count']);
        $this->assertEquals(49, $calledTimes);
    }

    /**
     * @test
     */
    public function it_stops_projection_during_run_when_it_was_stopped_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projectionManager = $this->projectionManager;

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) use (&$calledTimes, $projectionManager): array {
                    static $wasReset = false;

                    if (! $wasReset) {
                        $projectionManager->stopProjection('test_projection');
                        $wasReset = true;
                    }

                    $state['count']++;
                    $calledTimes++;

                    return $state;
                },
            ])
            ->run(false);

        $projection->run(false);

        $this->assertEquals(49, $projection->getState()['count']);
        $this->assertEquals(49, $calledTimes);
    }

    /**
     * @test
     */
    public function it_resets_to_empty_array(): void
    {
        $projection = $this->projectionManager->createProjection('test_projection');

        $state = $projection->getState();

        $this->assertInternalType('array', $state);

        $projection->reset();

        $state2 = $projection->getState();

        $this->assertInternalType('array', $state2);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_init_callback_provided_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->init(function (): array {
            return [];
        });
        $projection->init(function (): array {
            return [];
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->fromStream('foo');
        $projection->fromStream('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_2(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->fromStreams('foo');
        $projection->fromCategory('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_3(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->fromCategory('foo');
        $projection->fromStreams('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_4(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->fromCategories('foo');
        $projection->fromCategories('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_5(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->fromCategories('foo');
        $projection->fromAll('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_when_called_twice_(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->when(['foo' => function (): void {
        }]);
        $projection->when(['foo' => function (): void {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->when(['1' => function (): void {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured_2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->when(['foo' => 'invalid']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_whenAny_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection->whenAny(function (): void {
        });
        $projection->whenAny(function (): void {
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_on_run_when_nothing_configured(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->projectionManager->createProjection('test_projection');
        $projection->run();
    }

    /**
     * @test
     */
    public function it_links_to_and_loads_and_continues_again(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->fromStream('user-123')
            ->whenAny(
                function (array $state, Message $event): array {
                    $this->linkTo('foo', $event);

                    if ($event->metadata()['_aggregate_version'] === 50
                        || $event->metadata()['_aggregate_version'] === 100
                    ) {
                        $this->stop();
                    }

                    return $state;
                }
            )
            ->run();

        $events = $this->eventStore->load(new StreamName('foo'));

        $this->assertCount(50, $events);

        $events = [];
        for ($i = 51; $i < 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }
        $events[] = UsernameChanged::with([
            'name' => 'Oliver',
        ], 100);

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $projection->run();

        $events = $this->eventStore->load(new StreamName('foo'));

        $this->assertCount(100, $events);
    }

    /**
     * @test
     */
    public function it_emits_events_and_resets(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;

        $projection = $this->projectionManager->createProjection('test_projection');
        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event) use ($testCase): void {
                    $testCase->assertEquals('user-123', $this->streamName());
                    $this->emit($event);
                    $this->stop();
                },
            ])
            ->run();

        $events = $this->eventStore->load(new StreamName('test_projection'));

        $this->assertCount(1, $events);
        $this->assertEquals('Alex', $events->current()->payload()['name']);

        $projection->reset();
        $this->assertEquals('test_projection', $projection->getName());

        $this->expectException(StreamNotFound::class);
        $this->eventStore->load(new StreamName('test_projection'));
    }

    /**
     * @test
     */
    public function it_emits_events_and_deletes(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');
        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event): array {
                    $this->emit($event);

                    return $state;
                },
            ])
            ->run(false);

        $events = $this->eventStore->load(new StreamName('test_projection'));

        $this->assertCount(1, $events);
        $this->assertEquals('Alex', $events->current()->payload()['name']);

        $projection->delete(true);

        $this->expectException(StreamNotFound::class);
        $this->eventStore->load(new StreamName('test_projection'));
    }

    /**
     * @test
     */
    public function it_persists_in_single_handler(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromCategories('user', 'guest')
            ->whenAny(function (array $state, Message $event): array {
                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(50, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_persists_in_handlers(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createProjection('test_projection');

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromCategories('user', 'guest')
            ->when([
                UsernameChanged::class => function (array $state, Message $event): array {
                    $state['count']++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(49, $projection->getState()['count']);
    }

    protected function prepareEventStream(string $name): void
    {
        $events = [];
        $events[] = UserCreated::with([
            'name' => 'Alex',
        ], 1);
        for ($i = 2; $i < 50; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }
        $events[] = UsernameChanged::with([
            'name' => 'Sascha',
        ], 50);

        $this->eventStore->create(new Stream(new StreamName($name), new ArrayIterator($events)));
    }
}
