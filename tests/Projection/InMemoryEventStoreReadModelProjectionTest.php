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

use ArrayIterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\EventStoreTestCase;
use ProophTest\EventStore\Mock\ReadModelMock;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

class InMemoryEventStoreReadModelProjectionTest extends EventStoreTestCase
{
    /**
     * @test
     */
    public function it_can_projection_from_stream_and_reset(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $this->assertEquals(49, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_be_stopped_while_processing(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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
    public function it_can_query_from_category_with_when_all(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

    public function it_resumes_projection_from_position(): void
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $projection->run();

        $this->assertEquals(99, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_resets_to_empty_array(): void
    {
        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

        $projection->when(['foo' => 'invalid']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_whenAny_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);
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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

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

    private function prepareEventStream(string $name): void
    {
        $events = [];
        $events[] = UserCreated::with([
            'name' => 'Alex',
        ], 1);
        for ($i = 2; $i < 50; $i++) {
            $events[] = UsernameChanged::with([
                'name' => uniqid('name_'),
            ], $i);
        }
        $events[] = UsernameChanged::with([
            'name' => 'Sascha',
        ], 50);

        $this->eventStore->create(new Stream(new StreamName($name), new ArrayIterator($events)));
    }
}
