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
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Projection\InMemoryEventStoreProjection;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use ProophTest\EventStore\TestCase;

class InMemoryEventStoreProjectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_links_to(): void
    {
        $this->prepareEventStream('user-123');

        $testCase = $this;

        $projection = new InMemoryEventStoreProjection($this->eventStore, 'test_projection', true, 100);
        $projection
            ->fromStream('user-123')
            ->whenAny(
                function (array $state, Message $event) use ($testCase): array {
                    $this->linkTo('foo', $event);
                    $testCase->assertEquals('user-123', $this->streamName());

                    if ($event->metadata()['_aggregate_version'] === 50) {
                        $this->stop();
                    }

                    return $state;
                }
            )
            ->run();

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

        $projection = new InMemoryEventStoreProjection($this->eventStore, 'test_projection', true, 1);
        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event) use ($testCase): void {
                    $testCase->assertEquals('user-123', $this->streamName());
                    $this->emit($event);
                    $this->stop();
                }
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

        $projection = new InMemoryEventStoreProjection($this->eventStore, 'test_projection', true, 100);
        $projection
            ->fromStream('user-123')
            ->when([
                UserCreated::class => function (array $state, UserCreated $event): array {
                    $this->emit($event);
                    $this->stop();

                    return $state;
                }
            ])
            ->run();

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
    public function it_doesnt_emits_events_when_disabled(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method class@anonymous::emit()');

        $this->prepareEventStream('user-123');

        $projection = new InMemoryEventStoreProjection($this->eventStore, 'test_projection', false, 100);
        $projection
            ->fromStream('user-123')
            ->whenAny(function (array $state, Message $event): void {
                $this->emit($event);
            })
            ->run();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_run_when_nothing_configured(): void
    {
        $this->expectException(RuntimeException::class);

        $projection = new InMemoryEventStoreProjection($this->eventStore, 'test_projection', false, 100);
        $projection->run();
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
