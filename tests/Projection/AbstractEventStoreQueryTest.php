<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

/**
 * Common tests for all event store query implementations
 */
abstract class AbstractEventStoreQueryTest extends TestCase
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
    public function it_can_query_from_stream_and_reset(): void
    {
        $this->prepareEventStream('user-123');

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(49, $query->getState()['count']);

        $query->reset();

        $query->run();

        $this->assertEquals(49, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_be_stopped_while_processing(): void
    {
        $this->prepareEventStream('user-123');

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(10, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_streams(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(100, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_stream_and_filter_with_metadata_matcher(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->projectionManager->createQuery();
        $metadataMatcher = (new MetadataMatcher())
            ->withMetadataMatch('_aggregate_version', Operator::EQUALS(), 10);

        $projection
            ->init(function (): array {
                return ['count' => 0, 'version' => null];
            })
            ->fromStream('user-123', $metadataMatcher)
            ->whenAny(
                function (array $state, Message $event): array {
                    $state['count']++;
                    $state['version'] = $event->metadata()['_aggregate_version'];

                    return $state;
                }
            )
            ->run();

        $this->assertEquals(1, $projection->getState()['count']);
        $this->assertEquals(10, $projection->getState()['version']);
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

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(100, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_can_query_from_category_with_when_any(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(100, $query->getState()['count']);
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

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(4, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_resumes_query_from_position(): void
    {
        $this->prepareEventStream('user-123');

        $query = $this->projectionManager->createQuery();

        $query
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
            ->run();

        $this->assertEquals(49, $query->getState()['count']);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => \uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $this->prepareEventStream('user-234');

        $query->run();

        $this->assertEquals(148, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_resets_to_empty_array(): void
    {
        $query = $this->projectionManager->createQuery();

        $state = $query->getState();

        $this->assertIsArray($state);

        $query->reset();

        $state2 = $query->getState();

        $this->assertIsArray($state2);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_init_callback_provided_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->init(function (): array {
            return [];
        });
        $query->init(function (): array {
            return [];
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->fromStream('foo');
        $query->fromStream('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_2(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->fromStreams('foo');
        $query->fromCategory('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_3(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->fromCategory('foo');
        $query->fromStreams('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_4(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->fromCategories('foo');
        $query->fromCategories('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_5(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->fromCategories('foo');
        $query->fromAll('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_when_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->when(['foo' => function (): void {
        }]);
        $query->when(['foo' => function (): void {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = $this->projectionManager->createQuery();

        $query->when(['1' => function (): void {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured_2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = $this->projectionManager->createQuery();

        $query->when(['foo' => 'invalid']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_whenAny_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();

        $query->whenAny(function (): void {
        });
        $query->whenAny(function (): void {
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_on_run_when_nothing_configured(): void
    {
        $this->expectException(RuntimeException::class);

        $query = $this->projectionManager->createQuery();
        $query->run();
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
