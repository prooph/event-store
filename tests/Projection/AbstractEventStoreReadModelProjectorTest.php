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
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\ReadModelMock;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

/**
 * Common tests for all event store read model projector implementations
 */
abstract class AbstractEventStoreReadModelProjectorTest extends TestCase
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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $this->assertEquals('test_projection', $projection->getName());

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event): array {
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
    }

    /**
     * @test
     */
    public function it_can_project_from_stream_and_delete(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $this->assertEquals('test_projection', $projection->getName());

        $projection
            ->init(function (): array {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event): array {
                    $state['count']++;

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals(49, $projection->getState()['count']);

        $projection->delete(true);

        $this->assertFalse($readModel->isInitialized());
    }

    /**
     * @test
     */
    public function it_can_be_stopped_while_processing(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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
    public function it_resets_to_empty_array(): void
    {
        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->fromStream('foo');
        $projection->fromStream('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_2(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->fromStreams('foo');
        $projection->fromCategory('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_3(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->fromCategory('foo');
        $projection->fromStreams('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_4(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->fromCategories('foo');
        $projection->fromCategories('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_5(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->fromCategories('foo');
        $projection->fromAll('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_when_called_twice_(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->when(['1' => function (): void {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured_2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection->when(['foo' => 'invalid']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_whenAny_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);
        $projection->run();
    }

    /**
     * @test
     */
    public function it_updates_read_model_using_when(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection
            ->fromAll()
            ->when([
                UserCreated::class => function ($state, Message $event) use ($testCase): void {
                    $testCase->assertEquals('user-123', $this->streamName());
                    $this->readModel()->stack('insert', 'name', $event->payload()['name']);
                },
                UsernameChanged::class => function ($state, Message $event) use ($testCase): void {
                    $testCase->assertEquals('user-123', $this->streamName());
                    $this->readModel()->stack('update', 'name', $event->payload()['name']);

                    if ($event->payload()['name'] === 'Sascha') {
                        $this->stop();
                    }
                },
            ])
            ->run();

        $this->assertEquals('Sascha', $readModel->read('name'));

        $projection->reset();

        $this->assertFalse($readModel->hasKey('name'));
    }

    /**
     * @test
     */
    public function it_updates_read_model_using_when_any(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection
            ->init(function (): void {
                $this->readModel()->stack('insert', 'name', null);
            })
            ->fromStream('user-123')
            ->whenAny(function ($state, Message $event): void {
                $this->readModel()->stack('update', 'name', $event->payload()['name']);

                if ($event->payload()['name'] === 'Sascha') {
                    $this->stop();
                }
            })
            ->run();

        $this->assertEquals('Sascha', $readModel->read('name'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_run_two_projections_at_the_same_time(): void
    {
        $this->expectException(\Prooph\EventStore\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Another projection process is already running');

        $this->prepareEventStream('user-123');

        $projectionManager = $this->projectionManager;
        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

        $projection
            ->fromStream('user-123')
            ->whenAny(
                function (array $state, Message $event) use ($projectionManager): array {
                    $projection = $projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

                    $projection
                        ->fromStream('user-123')
                        ->whenAny(
                            function (array $state, Message $event): void {
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
    public function it_updates_projection_and_deletes(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel);

        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event): array {
                    $this->readModel()->stack('insert', 'name', $event->payload()['name']);
                    $this->stop();

                    return $state;
                },
            ])
            ->run(false);

        $this->assertEquals('Alex', $readModel->read('name'));

        $projection->delete(true);

        $this->assertFalse($readModel->isInitialized());
    }

    /**
     * @test
     */
    public function it_persists_using_single_handler(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel, [
            ReadModelProjector::OPTION_PERSIST_BLOCK_SIZE => 10,
        ]);

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

        $readModel = new ReadModelMock();

        $projection = $this->projectionManager->createReadModelProjection('test_projection', $readModel, [
            ReadModelProjector::OPTION_PERSIST_BLOCK_SIZE => 10,
        ]);

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

    /**
     * @test
     */
    public function it_deletes_projection_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
    public function it_deletes_projection_incl_emitted_events_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
        $this->assertEquals(49, $calledTimes);
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
        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
        $this->assertEquals(49, $calledTimes);
        $this->assertEquals([], $projectionManager->fetchProjectionNames('test_projection'));
    }

    /**
     * @test
     */
    public function it_resets_projection_before_start_when_it_was_reset_from_outside(): void
    {
        $this->prepareEventStream('user-123');

        $calledTimes = 0;

        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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

        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
        $projection = $this->projectionManager->createReadModelProjection('test_projection', new ReadModelMock());

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
    public function it_calls_reset_projection_also_if_init_callback_returns_state()
    {
        $readModel = $this->prophesize(ReadModel::class);
        $readModel->reset()->shouldBeCalled();

        $readModelProjection = $this->projectionManager->createReadModelProjection('test-projection', $readModel->reveal());

        $readModelProjection->init(function () {
            return ['state' => 'some value'];
        });

        $readModelProjection->reset();
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
