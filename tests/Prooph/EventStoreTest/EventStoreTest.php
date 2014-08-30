<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 21:27
 */

namespace Prooph\EventStoreTest;

use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class EventStoreTest
 *
 * @package Prooph\EventStoreTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventStoreTest extends TestCase
{
    /**
     * @var EventStore
     */
    private $eventStore;

    protected function setUp()
    {
        $inMemoryAdapter = new InMemoryAdapter();

        $config = new Configuration();

        $config->setAdapter($inMemoryAdapter);

        $this->eventStore = new EventStore($config);
    }

    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $streamEvent = new StreamEvent(
            EventId::generate(),
            new EventName('UserCreated'),
            array('name' => 'Alex', 'email' => 'contact@prooph.de'),
            1,
            new \DateTime()
        );

        $stream = new Stream(new StreamName('user'), array($streamEvent));

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('user'));

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertEquals(1, count($stream->streamEvents()));

        $this->assertEquals(1, count($recordedEvents));
    }
}
 