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
    public function it_can_be_stopped_while_processing()
    {
        $this->prepareEventStream('user-123');

        $readModel = new ReadModelMock();

        $projection = $this->eventStore->createReadModelProjection('test_projection', $readModel);

        $projection
            ->init(function (): array {
                $this->readModel()->stack('insert', 'count', 0);

                return ['count' => 0];
            })
            ->fromStream('user-123')
            ->whenAny(function ($state, Message $event): array {
                $state['count']++;

                if ($state['count'] === 10) {
                    $this->stop();
                }

                $this->readModel()->stack('update', 'count', $state['count']);

                return $state;
            })->run();

        $this->assertEquals(10, $projection->getState()['count']);
        $this->assertEquals(10, $readModel->read('count'));

        $projection->delete(true);

        $this->assertFalse($readModel->isInitialized());
    }

    /**
     * @test
     */
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
