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

use ArrayIterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Projection\InMemoryEventStoreQuery;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use ProophTest\EventStore\TestCase;

class InMemoryEventStoreQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_query_from_stream_and_reset()
    {
        $this->prepareEventStream('user-123');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->when([
                UsernameChanged::class => function (array $state, UsernameChanged $event) {
                    $state['count']++;
                    return $state;
                }
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
    public function it_can_query_from_streams(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromStreams('user-123', 'user-234')
            ->whenAny(
                function (array $state, Message $event) {
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
    public function it_can_query_from_all_ignoring_internal_streams(): void
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');
        $this->prepareEventStream('$iternal-345');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromAll()
            ->whenAny(
                function (array $state, Message $event) {
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
    public function it_can_query_from_category_with_when_all()
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromCategory('user')
            ->whenAny(
                function (array $state, Message $event) {
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
    public function it_can_query_from_categories_with_when()
    {
        $this->prepareEventStream('user-123');
        $this->prepareEventStream('user-234');
        $this->prepareEventStream('guest-345');
        $this->prepareEventStream('guest-456');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromCategories('user', 'guest')
            ->when([
                UserCreated::class => function (array $state, Message $event) {
                    $state['count']++;
                    return $state;
                }
            ])
            ->run();

        $this->assertEquals(4, $query->getState()['count']);
    }

    public function it_resumes_query_from_position(): void
    {
        $this->prepareEventStream('user-123');

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query
            ->init(function () {
                return ['count' => 0];
            })
            ->fromCategories('user', 'guest')
            ->when([
                UsernameChanged::class => function (array $state, Message $event) {
                    $state['count']++;
                    return $state;
                }
            ])
            ->run();

        $this->assertEquals(49, $query->getState()['count']);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => uniqid('name_')
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $query->run();

        $this->assertEquals(99, $query->getState()['count']);
    }

    /**
     * @test
     */
    public function it_resets_to_empty_array(): void
    {
        $query = new InMemoryEventStoreQuery($this->eventStore);

        $state = $query->getState();

        $this->assertInternalType('array', $state);

        $query->reset();

        $state2 = $query->getState();

        $this->assertInternalType('array', $state2);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_init_callback_provided_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->init(function () {
            return [];
        });
        $query->init(function () {
            return [];
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->fromStream('foo');
        $query->fromStream('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_2(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->fromStreams('foo');
        $query->fromCategory('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_3(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->fromCategory('foo');
        $query->fromStreams('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_4(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->fromCategories('foo');
        $query->fromCategories('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_from_called_twice_5(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->fromCategories('foo');
        $query->fromAll('bar');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_when_called_twice_(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->when(['foo' => function () {
        }]);
        $query->when(['foo' => function () {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->when(['1' => function () {
        }]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_handlers_configured_2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->when(['foo' => 'invalid']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_whenAny_called_twice(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);

        $query->whenAny(function () {
        });
        $query->whenAny(function () {
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_on_run_when_nothing_configured(): void
    {
        $this->expectException(RuntimeException::class);

        $query = new InMemoryEventStoreQuery($this->eventStore);
        $query->run();
    }

    private function prepareEventStream(string $name): void
    {
        $events = [];
        $events[] = UserCreated::with([
            'name' => 'Alex'
        ], 1);
        for ($i = 2; $i < 50; $i++) {
            $events[] = UsernameChanged::with([
                'name' => uniqid('name_')
            ], $i);
        }
        $events[] = UsernameChanged::with([
            'name' => 'Sascha'
        ], 50);

        $this->eventStore->create(new Stream(new StreamName($name), new ArrayIterator($events)));
    }
}
