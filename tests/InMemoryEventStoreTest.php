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

namespace ProophTest\EventStore;

use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\UserCreated;

class InMemoryEventStoreTest extends AbstractEventStoreTest
{
    use EventStoreTestStreamTrait;
    use TransactionalEventStoreTestTrait;

    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
    }

    /**
     * @test
     */
    public function it_rolls_back_events(): void
    {
        $streamName = new StreamName('Prooph\Model\User');
        $this->eventStore->create($this->getTestStream());

        $streamEventTwo = UserCreated::with(
            ['name' => 'Sascha', 'email' => 'contact@prooph.de'],
            1
        );
        $streamEventThree = UserCreated::with(
            ['name' => 'Sandro', 'email' => 'contact@prooph.de'],
            1
        );

        $this->eventStore->beginTransaction();
        $this->eventStore->appendTo($streamName, new \ArrayIterator([$streamEventTwo, $streamEventThree]));
        $this->eventStore->rollback();
        $events = $this->eventStore->load($streamName);
        $this->assertCount(1, $events);
        $this->assertSame('Alex', $events[0]->payload()['name']);
    }

    /**
     * @test
     */
    public function it_can_read_not_committed_events(): void
    {
        $streamName = new StreamName('Prooph\Model\User');
        $this->eventStore->create($this->getTestStream());

        $streamEventTwo = UserCreated::with(
            ['name' => 'Sascha', 'email' => 'contact@prooph.de'],
            1
        );
        $streamEventThree = UserCreated::with(
            ['name' => 'Sandro', 'email' => 'contact@prooph.de'],
            1
        );

        $this->eventStore->beginTransaction();
        $this->eventStore->appendTo($streamName, new \ArrayIterator([$streamEventTwo, $streamEventThree]));

        $events = $this->eventStore->load($streamName);

        $this->assertCount(3, $events);
        $this->assertSame('Alex', $events[0]->payload()['name']);
        $this->assertSame('Sascha', $events[1]->payload()['name']);
        $this->assertSame('Sandro', $events[2]->payload()['name']);

        $this->eventStore->commit();

        $events = $this->eventStore->load($streamName);
        $this->assertCount(3, $events);
    }
}
