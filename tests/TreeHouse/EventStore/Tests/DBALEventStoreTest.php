<?php

namespace TreeHouse\EventStore\Tests;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\Assert;
use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;
use TreeHouse\EventStore\DBALEventStore;
use TreeHouse\EventStore\Event;
use TreeHouse\EventStore\EventFactory;
use TreeHouse\EventStore\EventStream;
use TreeHouse\EventStore\SerializedEvent;
use TreeHouse\EventStore\Upcasting\SimpleUpcasterChain;
use TreeHouse\Serialization\SerializerInterface;

class DBALEventStoreTest extends PHPUnit_Framework_TestCase
{
    const UUID = 'EC16EE75-5404-40D5-B96C-AAFEF7790DE8';

    /**
     * @var DBALEventStore
     */
    private $eventStore;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Statement
     */
    private $statement;

    /**
     * @var EventFactory
     */
    private $eventFactory;

    public function setUp()
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->connection = $this->prophesize(Connection::class);
        $this->statement = $this->prophesize(Statement::class);
        $this->eventFactory = $this->prophesize(EventFactory::class);

        $this->eventStore = new DBALEventStore(
            $this->connection->reveal(),
            $this->serializer->reveal(),
            $this->eventFactory->reveal()
        );
    }

    /**
     * @test
     */
    public function it_returns_event_stream()
    {
        $dbEvents = [
            [
                'uuid' => self::UUID,
                'name' => 'Test',
                'payload' => '{"payload":"json"}',
                'payload_version' => 1,
                'version' => 1,
                'datetime_created' => '2015-01-01 00:00:00',
            ],
            [
                'uuid' => self::UUID,
                'name' => 'Test',
                'payload' => '{"payload":"json"}',
                'payload_version' => 1,
                'version' => 2,
                'datetime_created' => '2015-01-02 00:00:00',
            ],
        ];

        $serializedEvents = array_map(function ($event) {
            return new SerializedEvent(
                $event['uuid'],
                $event['name'],
                $event['payload'],
                $event['payload_version'],
                $event['version'],
                new DateTime($event['datetime_created'])
            );
        }, $dbEvents);

        $this->statement->fetchAll()->willReturn($dbEvents);

        $this->connection->createQueryBuilder()->willReturn(new QueryBuilder($this->connection->reveal()));
        $this->connection->executeQuery(Argument::cetera())->willReturn($this->statement->reveal());

        $this->serializer->deserialize('Test', '{"payload":"json"}')->willReturn(new stdClass());

        $this->eventFactory->createFromSerializedEvent($serializedEvents[0])->willReturn(
            new Event(
                $dbEvents[0]['uuid'],
                $dbEvents[0]['name'],
                new stdClass(),
                $dbEvents[0]['payload_version'],
                $dbEvents[0]['version'],
                new \DateTime($dbEvents[0]['datetime_created'])
            )
        );
        $this->eventFactory->createFromSerializedEvent($serializedEvents[1])->willReturn(
            new Event(
                $dbEvents[1]['uuid'],
                $dbEvents[1]['name'],
                new stdClass(),
                $dbEvents[1]['payload_version'],
                $dbEvents[1]['version'],
                new \DateTime($dbEvents[1]['datetime_created'])
            )
        );

        $eventStream = $this->eventStore->getStream(self::UUID);

        $this->assertEquals(
            $totalEvents = 2,
            count($eventStream)
        );

        $events = iterator_to_array($eventStream);

        $this->assertEvent(
            [
                'uuid' => self::UUID,
                'name' => 'Test',
                'payload' => new stdClass(),
                'payload_version' => 1,
                'version' => 1,
                'date' => '2015-01-01',
            ],
            $firstEvent = $events[0]
        );

        $this->assertEvent(
            [
                'uuid' => self::UUID,
                'name' => 'Test',
                'payload' => new stdClass(),
                'payload_version' => 1,
                'version' => 2,
                'date' => '2015-01-02',
            ],
            $secondEvent = $events[1]
        );
    }

    /**
     * @test
     */
    public function it_returns_partial_event_stream()
    {
        $dbEvents = [
            [
                'uuid' => self::UUID,
                'name' => 'Test',
                'payload' => '{"payload":"json"}',
                'payload_version' => 1,
                'version' => 2,
                'datetime_created' => '2015-01-02 00:00:00',
            ],
        ];

        $this->statement->fetchAll()->willReturn($dbEvents);

        $fromVersion = 1;
        $toVersion = 2;

        $this->connection->createQueryBuilder()->willReturn(new QueryBuilder($this->connection->reveal()));

        $this->connection->executeQuery(
            Argument::that(function($arg) {
            Assert::assertContains('AND (version > :version_from)', $arg);
            Assert::assertContains('AND (version <= :version_to)', $arg);

            return true;
        }), Argument::that(function($arg) use ($fromVersion, $toVersion) {
            Assert::assertArraySubset([
                'version_from' => $fromVersion,
                'version_to' => $toVersion,
            ], $arg);

            return true;
        }), Argument::cetera())->willReturn($this->statement->reveal());

        $this->serializer->deserialize('Test', '{"payload":"json"}')->willReturn(new stdClass());

        $eventStream = $this->eventStore->getPartialStream(self::UUID, $fromVersion, $toVersion);

        $this->assertEquals(
            $totalEvents = 1,
            count($eventStream)
        );
    }

    /**
     * @test
     */
    public function it_appends_events()
    {
        $this->connection->beginTransaction()->shouldBeCalled();
        $this->connection->insert(Argument::cetera())->shouldBeCalled();
        $this->connection->commit()->shouldBeCalled();

        $this->eventStore->append(
            new EventStream([
                $this->getEvent($version = 1)->reveal(),
                $this->getEvent($version = 2)->reveal(),
            ])
        );

        $this->connection->rollBack()->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @expectedException \TreeHouse\EventStore\EventStoreException
     */
    public function it_throws_when_version_is_duplicate()
    {
        $this->connection->beginTransaction()->shouldBeCalled();
        $this->connection->insert(Argument::cetera())->willThrow(DBALException::class);
        $this->connection->rollBack()->shouldBeCalled();

        $this->eventStore->append(
            new EventStream([
                $this->getEvent($version = 1)->reveal(),
                $this->getEvent($version = 1)->reveal(),
            ])
        );
    }

    /**
     * @test
     * @expectedException \TreeHouse\EventStore\EventStreamNotFoundException
     */
    public function it_throws_when_stream_is_not_found()
    {
        $uuid = 'unknown_stream_identifier';

        $this->statement->fetchAll()->willReturn([]);

        $this->connection->createQueryBuilder()->willReturn(new QueryBuilder($this->connection->reveal()));
        $this->connection->executeQuery(Argument::cetera())->willReturn($this->statement->reveal());

        $this->eventStore->getStream($uuid);
    }

    /**
     * @test
     */
    public function it_returns_empty_when_partial_stream_is_not_found()
    {
        $uuid = 'unknown_stream_identifier';

        $this->statement->fetchAll()->willReturn([]);

        $this->connection->createQueryBuilder()->willReturn(new QueryBuilder($this->connection->reveal()));
        $this->connection->executeQuery(Argument::cetera())->willReturn($this->statement->reveal());

        $stream = $this->eventStore->getPartialStream($uuid, 0);

        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function it_upcasts_events()
    {
        // setup

        /** @var SerializerInterface|ObjectProphecy $serializer */
        $serializer = $this->prophesize(SerializerInterface::class);

        $eventStore = new DBALEventStore(
            $this->connection->reveal(),
            $serializer->reveal(),
            $eventFactory = new EventFactory($serializer->reveal())
        );

        $dbEvents = [
            [
                'uuid' => self::UUID,
                'name' => 'Anything',
                'payload' => '{"payload":"json"}',
                'payload_version' => 1,
                'version' => 1,
                'datetime_created' => '2015-01-01 00:00:00',
            ],
        ];

        $this->statement->fetchAll()->willReturn($dbEvents);

        $this->connection->createQueryBuilder()->willReturn(new QueryBuilder($this->connection->reveal()));
        $this->connection->executeQuery(Argument::cetera())->willReturn($this->statement->reveal());

        $upcastedEvent = new SerializedEvent(
            self::UUID,
            'Anything',
            '{"payload":"json", "with": "extra", "data": true}',
            2,
            1,
            new DateTime('2015-01-01 00:00:00')
        );

        $someUpcaster = new DummyUpcaster(function (SerializedEvent $e) {
            return $e->getName() == 'Anything' && $e->getPayloadVersion() == 1;
        }, $upcastedEvent);

        $chain = new SimpleUpcasterChain();
        $chain->registerUpcaster($someUpcaster);

        // test

        $eventStore->setUpcaster($chain);

        $stream = $eventStore->getStream(self::UUID);

        $this->assertEquals(
            $eventFactory->createFromSerializedEvent($upcastedEvent),
            iterator_to_array($stream)[0]
        );
    }

    /**
     * @param $version
     * @param string $uuid
     * @param string $name
     * @param null   $payload
     * @param string $dateTime
     * @param int    $payloadVersion
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getEvent(
        $version,
        $uuid = self::UUID,
        $name = 'Test',
        $payload = null,
        $dateTime = 'now',
        $payloadVersion = 1
    ) {
        if (null === $payload) {
            $payload = new DummyEvent();
        }

        $event = $this->prophesize(Event::class);
        $event->getId()->willReturn($uuid);
        $event->getName()->willReturn($name);
        $event->getVersion()->willReturn($version);
        $event->getPayload()->willReturn($payload);
        $event->getPayloadVersion()->willReturn($payloadVersion);
        $event->getDate()->willReturn(new DateTime($dateTime));

        return $event;
    }

    /**
     * @param array $expected
     * @param Event $event
     */
    private function assertEvent(array $expected, Event $event)
    {
        $this->assertEquals(
            [
                $expected['uuid'],
                $expected['name'],
                $expected['payload'],
                $expected['version'],
                $expected['date'],
            ],
            [
                $event->getId(),
                $event->getName(),
                $event->getPayload(),
                $event->getVersion(),
                $event->getDate()->format('Y-m-d'),
            ]
        );
    }
}
