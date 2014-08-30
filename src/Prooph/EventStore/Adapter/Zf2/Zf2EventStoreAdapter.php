<?php

/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Adapter\Zf2;

use Prooph\EventStore\Adapter\AdapterInterface;
use Prooph\EventStore\Adapter\Exception\ConfigurationException;
use Prooph\EventStore\Adapter\Exception\InvalidArgumentException;
use Prooph\EventStore\Adapter\Feature\TransactionFeatureInterface;
use Prooph\EventStore\Stream\AggregateType;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Zend\Db\Sql\Ddl\Column\Integer;
use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\DropTable;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Platform;
use Zend\Serializer\Serializer;

/**
 * EventStore Adapter Zf2EventStoreAdapter
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 */
class Zf2EventStoreAdapter implements AdapterInterface, TransactionFeatureInterface
{

    /**
     * @var ZendDbAdapter 
     */
    protected $dbAdapter;

    /**
     *
     * @var TableGateway[] 
     */
    protected $tableGateways;

    /**
     * Custom sourceType to table mapping
     * 
     * @var array 
     */
    protected $aggregateTypeTableMap = array();

    /**
     * Name of the table that contains snapshot metadata
     * 
     * @var string 
     */
    protected $snapshotTable = 'snapshot';

    /**
     * @param array $configuration
     * @throws \Prooph\EventStore\Adapter\Exception\ConfigurationException
     */
    public function __construct(array $configuration)
    {
        if (!isset($configuration['connection']) && !isset($configuration['zend_db_adapter'])) {
            throw new ConfigurationException('DB adapter configuration is missing');
        }

        if (isset($configuration['source_table_map'])) {
            $this->aggregateTypeTableMap = $configuration['source_table_map'];
        }

        if (isset($configuration['snapshot_table'])) {
            $this->snapshotTable = $configuration['snapshot_table'];
        }

        $this->dbAdapter = (isset($configuration['zend_db_adapter']))?
            $configuration['zend_db_adapter'] :
            new ZendDbAdapter($configuration['connection']);
    }

    /**
     * @param AggregateType $aggregateType
     * @param StreamName $streamId
     * @param null|int $version
     * @return Stream
     * @throws \Prooph\EventStore\Adapter\Exception\InvalidArgumentException
     */
    public function loadStream(AggregateType $aggregateType, StreamName $streamId, $version = null)
    {
        try {
            \Assert\that($version)->nullOr()->integer();
        } catch (\InvalidArgumentException $ex) {
            throw new InvalidArgumentException(
                sprintf(
                    'Loading the stream for AggregateType %s with name %s failed cause invalid parameters were passed: %s',
                    $aggregateType->toString(),
                    $streamId->toString(),
                    $ex->getMessage()
                )
            );
        }

        $tableGateway = $this->getTablegateway($aggregateType);

        $sql = $tableGateway->getSql();

        $where = new \Zend\Db\Sql\Where();

        $where->equalTo('streamId', $streamId->toString());

        if (!is_null($version)) {
            $where->AND->greaterThanOrEqualTo('version', $version);
        }

        $select = $sql->select()->where($where)->order('version');

        $eventsData = $tableGateway->selectWith($select);

        $events = array();

        foreach ($eventsData as $eventData) {
            $payload = Serializer::unserialize($eventData->payload);

            $eventId = new EventId($eventData->eventId);

            $eventName = new EventName($eventData->eventName);

            $occurredOn = new \DateTime($eventData->occurredOn);

            $events[] = new StreamEvent($eventId, $eventName, $payload, (int)$eventData->version, $occurredOn);
        }

        return new Stream($aggregateType, $streamId, $events);
    }

    /**
     * Add new stream to the source stream
     *
     * @param Stream $stream
     *
     * @return void
     */
    public function addToExistingStream(Stream $stream)
    {
        foreach ($stream->streamEvents() as $event) {
            $this->insertEvent($stream->aggregateType(), $stream->streamId(), $event);
        }
    }

    /**
     * @param AggregateType $aggregateType
     * @param StreamName $streamId
     */
    public function removeStream(AggregateType $aggregateType, StreamName $streamId)
    {
        $tableGateway = $this->getTablegateway($aggregateType);

        $tableGateway->delete(array('streamId' => $streamId->toString()));
    }

    /**
     * @param array $streams
     * @return bool
     * @throws \BadMethodCallException
     */
    public function createSchema(array $streams)
    {
        foreach ($streams as $stream) {

            $createTable = new CreateTable($this->getTable(new AggregateType($stream)));

            $createTable->addColumn(new Varchar('eventId', 200))
                ->addColumn(new Varchar('streamId', 200))
                ->addColumn(new Integer('version'))
                ->addColumn(new Text('eventName'))
                ->addColumn(new Text('payload'))
                ->addColumn(new Text('occurredOn'));

            $createTable->addConstraint(new PrimaryKey('eventId'));

            $this->dbAdapter->getDriver()
                ->getConnection()
                ->execute($createTable->getSqlString($this->dbAdapter->getPlatform()));

        }
    }

    /**
     * @param array $streams
     */
    public function dropSchema(array $streams)
    {
        foreach ($streams as $stream) {
            $dropTable = new DropTable($this->getTable(new AggregateType($stream)));

            $this->dbAdapter->getDriver()
                ->getConnection()
                ->execute($dropTable->getSqlString($this->dbAdapter->getPlatform()));
        }
    }

    public function beginTransaction()
    {
        $this->dbAdapter->getDriver()->getConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->dbAdapter->getDriver()->getConnection()->commit();
    }

    public function rollback()
    {
        $this->dbAdapter->getDriver()->getConnection()->rollback();
    }

    /**
     * Insert an event
     *
     * @param \Prooph\EventStore\Stream\AggregateType $aggregateType
     * @param \Prooph\EventStore\Stream\StreamName $streamId
     * @param \Prooph\EventStore\Stream\StreamEvent $e
     * @return void
     */
    protected function insertEvent(AggregateType $aggregateType, StreamName $streamId, StreamEvent $e)
    {
        $eventData = array(
            'eventId' => $e->eventId()->toString(),
            'streamId' => $streamId->toString(),
            'version' => $e->version(),
            'eventName' => $e->eventName()->toString(),
            'payload' => Serializer::serialize($e->payload()),
            'occurredOn' => $e->occurredOn()->format(\DateTime::ISO8601)
        );

        $tableGateway = $this->getTablegateway($aggregateType);

        $tableGateway->insert($eventData);
    }

    /**
     * Get the corresponding Tablegateway of the given $aggregateFQCN
     * 
     * @param AggregateType $aggregateType
     * 
     * @return TableGateway
     */
    protected function getTablegateway(AggregateType $aggregateType)
    {
        if (!isset($this->tableGateways[$aggregateType->toString()])) {
            $this->tableGateways[$aggregateType->toString()] = new TableGateway($this->getTable($aggregateType), $this->dbAdapter);
        }

        return $this->tableGateways[$aggregateType->toString()];
    }

    /**
     * Get tablename for given $aggregateFQCN
     * 
     * @param AggregateType $aggregateType
     * @return string
     */
    protected function getTable(AggregateType $aggregateType)
    {
        if (isset($this->aggregateTypeTableMap[$aggregateType->toString()])) {
            $tableName = $this->aggregateTypeTableMap[$aggregateType->toString()];
        } else {
            $tableName = strtolower($this->getShortAggregateType($aggregateType)) . "_stream";
        }

        return $tableName;
    }

    /**
     * @param AggregateType $aggregateType
     * @return string
     */
    protected function getShortAggregateType(AggregateType $aggregateType)
    {
        return join('', array_slice(explode('\\', $aggregateType->toString()), -1));
    }
}
