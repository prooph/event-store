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
use Prooph\EventStore\Adapter\Builder\EventBuilder;
use Prooph\EventStore\Adapter\Exception\ConfigurationException;
use Prooph\EventStore\Adapter\Exception\InvalidArgumentException;
use Prooph\EventStore\Adapter\Feature\TransactionFeatureInterface;
use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Rhumsaa\Uuid\Uuid;
use ValueObjects\DateTime\DateTime;
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
        if (!isset($configuration['connection'])) {
            throw new ConfigurationException('DB adapter connection configuration is missing');
        }

        if (isset($options['source_table_map'])) {
            $this->aggregateTypeTableMap = $options['source_table_map'];
        }

        if (isset($options['snapshot_table'])) {
            $this->snapshotTable = $options['snapshot_table'];
        }

        $this->dbAdapter = new ZendDbAdapter($configuration['connection']);
    }

    /**
     * @param string   $aggregateFQCN
     * @param string   $aggregateId
     * @param null|int $version
     * @return AggregateChangedEvent[]
     * @throws \Prooph\EventStore\Adapter\Exception\InvalidArgumentException
     */
    public function loadStream($aggregateFQCN, $aggregateId, $version = null)
    {
        try {
            \Assert\that($aggregateFQCN)->notEmpty()->string();
            \Assert\that($aggregateId)->notEmpty()->string();
            \Assert\that($version)->nullOr()->integer();
        } catch (\InvalidArgumentException $ex) {
            throw new InvalidArgumentException(
                sprintf(
                    'Loading the stream for Aggregate %s (%s) failed cause invalid parameters were passed: %s',
                    (string)$aggregateId,
                    (string)$aggregateFQCN,
                    $ex->getMessage()
                )
            );
        }

        $tableGateway = $this->getTablegateway($aggregateFQCN);

        $sql = $tableGateway->getSql();

        $where = new \Zend\Db\Sql\Where();

        $where->equalTo('aggregateId', $aggregateId);

        if (!is_null($version)) {
            $where->AND->greaterThanOrEqualTo('version', $version);
        }

        $select = $sql->select()->where($where)->order('version');

        $eventsData = $tableGateway->selectWith($select);

        $events = array();

        foreach ($eventsData as $eventData) {
            $payload = Serializer::unserialize($eventData->payload);

            $uuid = Uuid::fromString($eventData->uuid);

            $dateTime = new \DateTime($eventData->occurredOn);

            $occurredOn = DateTime::fromNativeDateTime($dateTime);

            $events[] = EventBuilder::reconstructEvent(
                (string)$eventData->eventClass,
                $uuid,
                $aggregateId,
                $occurredOn,
                (int)$eventData->version,
                (array)$payload
            );
        }

        return $events;
    }

    /**
     * @param string $aggregateFQCN
     * @param string $aggregateId
     * @param AggregateChangedEvent[] $events
     */
    public function addToStream($aggregateFQCN, $aggregateId, $events)
    {
        foreach ($events as $event) {
            $this->insertEvent($aggregateFQCN, $aggregateId, $event);
        }
    }

    /**
     * @param string $aggregateFQCN
     * @param string $aggregateId
     */
    public function removeStream($aggregateFQCN, $aggregateId)
    {
        $tableGateway = $this->getTablegateway($aggregateFQCN);

        $tableGateway->delete(array('aggregateId' => $aggregateId));
    }

    /**
     * @param array $streams
     * @return bool
     * @throws \BadMethodCallException
     */
    public function createSchema(array $streams)
    {
        foreach ($streams as $stream) {

            $createTable = new CreateTable($this->getTable($stream));

            $createTable->addColumn(new Varchar('uuid', 36))
                ->addColumn(new Varchar('aggregateId', 200))
                ->addColumn(new Integer('version'))
                ->addColumn(new Text('eventClass'))
                ->addColumn(new Text('payload'))
                ->addColumn(new Text('occurredOn'));

            $createTable->addConstraint(new PrimaryKey('uuid'));

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
            $dropTable = new DropTable($this->getTable($stream));

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
     * @param string                $aggregateFQCN
     * @param string                $aggregateId
     * @param AggregateChangedEvent $e
     * 
     * @return void
     */
    protected function insertEvent($aggregateFQCN, $aggregateId, AggregateChangedEvent $e)
    {
        $eventData = array(
            'uuid' => $e->uuid()->toString(),
            'aggregateId' => $aggregateId,
            'version' => $e->version(),
            'eventClass' => get_class($e),
            'payload' => Serializer::serialize($e->payload()),
            'occurredOn' => $e->occurredOn()->toNativeDateTime()->format(\DateTime::ISO8601)
        );

        $tableGateway = $this->getTablegateway($aggregateFQCN);

        $tableGateway->insert($eventData);
    }

    /**
     * Get the corresponding Tablegateway of the given $aggregateFQCN
     * 
     * @param string $aggregateFQCN
     * 
     * @return TableGateway
     */
    protected function getTablegateway($aggregateFQCN)
    {
        if (!isset($this->tableGateways[$aggregateFQCN])) {
            $this->tableGateways[$aggregateFQCN] = new TableGateway($this->getTable($aggregateFQCN), $this->dbAdapter);
        }

        return $this->tableGateways[$aggregateFQCN];
    }

    /**
     * Get tablename for given $aggregateFQCN
     * 
     * @param $aggregateFQCN
     * @return string
     */
    protected function getTable($aggregateFQCN)
    {
        if (isset($this->aggregateTypeTableMap[$aggregateFQCN])) {
            $tableName = $this->aggregateTypeTableMap[$aggregateFQCN];
        } else {
            $tableName = strtolower($this->getShortAggregateType($aggregateFQCN)) . "_stream";
        }

        return $tableName;
    }

    /**
     * @param string $aggregateFQCN
     * @return string
     */
    protected function getShortAggregateType($aggregateFQCN)
    {
        return join('', array_slice(explode('\\', $aggregateFQCN), -1));
    }
}
