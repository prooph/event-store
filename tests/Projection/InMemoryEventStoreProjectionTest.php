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
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\EventStoreTestCase;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

class InMemoryEventStoreProjectionTest extends EventStoreTestCase
{
    /**
     * @test
     */
    public function it_links_to(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;

        $projection = $this->eventStore->createProjection('test_projection');
        $projection
            ->fromStream('user-123')
            ->whenAny(
                function (array $state, Message $event) use ($testCase): array {
                    $this->linkTo('foo', $event);
                    $testCase->assertEquals('user-123', $this->streamName());

                    return $state;
                }
            )
            ->run(false);

        $streams = $this->eventStore->load(new StreamName('foo'));
        $events = $streams->streamEvents();

        $this->assertCount(50, $events);
    }

    /**
     * @test
     */
    public function it_emits_events_and_resets(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;

        $projection = $this->eventStore->createProjection('test_projection');
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

        $streams = $this->eventStore->load(new StreamName('test_projection'));
        $events = $streams->streamEvents();

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

        $projection = $this->eventStore->createProjection('test_projection');
        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event): array {
                    $this->emit($event);

                    return $state;
                },
            ])
            ->run(false);

        $streams = $this->eventStore->load(new StreamName('test_projection'));
        $events = $streams->streamEvents();

        $this->assertCount(1, $events);
        $this->assertEquals('Alex', $events->current()->payload()['name']);

        $projection->delete(true);

        $this->expectException(StreamNotFound::class);
        $this->eventStore->load(new StreamName('test_projection'));
    }

    /**
     * @test
     */
    public function it_throws_exception_on_run_when_nothing_configured(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = $this->eventStore->createProjection('test_projection');
        $projection->run();
    }

    /**
     * @test
     */
    public function it_resumes_projection_from_position(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->eventStore->createProjection('test_projection');

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

        $projection->run(false);

        $events = [];
        for ($i = 51; $i <= 100; $i++) {
            $events[] = UsernameChanged::with([
                'name' => uniqid('name_'),
            ], $i);
        }

        $this->eventStore->appendTo(new StreamName('user-123'), new ArrayIterator($events));

        $projection->run(false);

        $this->assertEquals(99, $projection->getState()['count']);
    }

    /**
     * @test
     */
    public function it_persists_in_single_handler(): void
    {
        $this->prepareEventStream('user-123');

        $projection = $this->eventStore->createProjection('test_projection', new ProjectionOptions(100, 10));

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

        $projection = $this->eventStore->createProjection('test_projection', new ProjectionOptions(100, 10));

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
