<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore;

use Prooph\EventStore\Adapter\AdapterInterface;
use Prooph\EventStore\Adapter\Feature\TransactionFeatureInterface;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Mapping\AggregateTypeProviderInterface;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
use Prooph\EventStore\Repository\RepositoryInterface;
use Prooph\EventStore\Stream\AggregateType;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;

/**
 * EventStore 
 *
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore
 */
class EventStore 
{
    /**
     *
     * @var AdapterInterface 
     */
    protected $adapter;

    /**
     * @var EventManager
     */
    protected $persistenceEvents;

    /**
     * @var array
     */
    protected $recordedEvents = array();

    /**
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * Construct
     * 
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->adapter = $config->getAdapter();

        $config->setUpEventStoreEnvironment($this);
    }

    /**
     * Get the active EventStoreAdapter
     * 
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param Stream $aStream
     * @throws Exception\RuntimeException
     * @return void
     */
    public function create(Stream $aStream)
    {
        $argv = array('stream' => $aStream);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
            throw new RuntimeException('Stream creation failed. EventStore is not in an active transaction');
        }

        $aStream = $event->getParam('stream');

        $this->adapter->create($aStream);

        $this->recordedEvents = array_merge($this->recordedEvents, $aStream->streamEvents());

        $event->setName(__FUNCTION__ . '.post');

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * @param StreamName $aStreamName
     * @param array $streamEvents
     * @throws Exception\RuntimeException
     * @return void
     */
    public function appendTo(StreamName $aStreamName, array $streamEvents)
    {
        \Assert\that($streamEvents)->all()->isInstanceOf('Prooph\EventStore\Stream\StreamEvent');

        $argv = array('streamName' => $aStreamName, 'streamEvents' => $streamEvents);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
            throw new RuntimeException('Append events to stream failed. EventStore is not in an active transaction');
        }

        $aStreamName = $event->getParam('streamName');
        $streamEvents = $event->getParam('streamEvents');

        $this->adapter->appendTo($aStreamName, $streamEvents);

        $this->recordedEvents = array_merge($this->recordedEvents, $streamEvents);

        $event->setName(__FUNCTION__, '.post');

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * @param StreamName $aStreamName
     * @throws Exception\RuntimeException
     * @return void
     */
    public function remove(StreamName $aStreamName)
    {
        $argv = array('streamName' => $aStreamName);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
            throw new RuntimeException('Remove stream failed. EventStore is not in an active transaction');
        }

        $aStreamName = $event->getParam('streamName');

        $this->adapter->remove($aStreamName);

        $event->setName(__FUNCTION__, '.post');

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * @param StreamName $aStreamName
     * @param array $metadata
     * @throws Exception\RuntimeException
     * @return void
     */
    public function removeEventsByMetadataFrom(StreamName $aStreamName, array $metadata)
    {
        $argv = array('streamName' => $aStreamName, 'metadata' => $metadata);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
            throw new RuntimeException('Remove events from stream failed. EventStore is not in an active transaction');
        }

        $aStreamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');

        $this->adapter->removeEventsByMetadataFrom($aStreamName, $metadata);

        $event->setName(__FUNCTION__, '.post');

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * @param StreamName $aStreamName
     * @throws Exception\StreamNotFoundException
     * @return Stream
     */
    public function load(StreamName $aStreamName)
    {
        $argv = array('streamName' => $aStreamName);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        if ($event->propagationIsStopped()) {
            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $aStreamName->toString()
                )
            );
        }

        $aStreamName = $event->getParam('streamName');

        $stream = $this->adapter->load($aStreamName);

        if (! $stream) {
            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $aStreamName->toString()
                )
            );
        }

        $event->setName(__FUNCTION__, '.post');

        $event->setParam('stream', $stream);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $aStreamName->toString()
                )
            );
        }

        return $event->getParam('stream');
    }

    /**
     * @param StreamName $aStreamName
     * @param array $metadata
     * @return StreamEvent[]
     */
    public function loadEventsByMetadataFrom(StreamName $aStreamName, array $metadata)
    {
        $argv = array('streamName' => $aStreamName, 'metadata' => $metadata);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        if ($event->propagationIsStopped()) {
            return array();
        }

        $aStreamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');

        $events = $this->adapter->loadEventsByMetadataFrom($aStreamName, $metadata);

        $event->setName(__FUNCTION__, '.post');

        $event->setParam('streamEvents', $events);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return array();
        }

        return $event->getParam('streamEvents');
    }

    /**
     * Begin transaction
     *
     * @triggers beginTransaction
     */
    public function beginTransaction()
    {
        if ($this->inTransaction) {
            throw new RuntimeException('Can not begin transaction. EventStore is already in a transaction');
        }

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->beginTransaction();
        }

        $this->inTransaction = true;

        $this->getPersistenceEvents()->trigger(__FUNCTION__, $this);
    }

    /**
     * Commit transaction
     *
     * @triggers commit.pre If a listener stops propagation, the ES performs a rollback
     * @triggers commit.post with all recorded StreamEvents. Perfect event to attach a domain event dispatcher
     */
    public function commit()
    {
        if (! $this->inTransaction) {
            throw new RuntimeException('Cannot commit transaction. EventStore has no active transaction');
        }

        $event = new PreCommitEvent(__FUNCTION__ . '.pre', $this);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            $this->rollback();
            return;
        }

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->commit();
        }

        $this->inTransaction = false;

        $argv = array('recordedEvents' => $this->recordedEvents);

        $event = new PostCommitEvent(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        $this->recordedEvents = array();
    }

    /**
     * Rollback transaction
     *
     * @triggers rollback
     */
    public function rollback()
    {
        if (! $this->inTransaction) {
            throw new RuntimeException('Cannot rollback transaction. EventStore has no active transaction');
        }

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->rollback();
        }

        $this->inTransaction = false;

        $this->getPersistenceEvents()->trigger(__FUNCTION__, $this);

        $this->recordedEvents = array();
    }



    /**
     * Register given EventSourcedAggregateRoot in the identity map
     *
     * @param object $anEventSourcedAggregateRoot
     *
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @triggers attach.pre
     * @triggers attach.post
     * @return void
     */
    public function attach($anEventSourcedAggregateRoot)
    {
        if (!is_object($anEventSourcedAggregateRoot)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Given aggregate is not an object. It is of type %s',
                    gettype($anEventSourcedAggregateRoot)
                )
            );
        }

        $argv = compact('anEventSourcedAggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        $aggregateType = $this->getAggregateType($anEventSourcedAggregateRoot);
        $streamId = $this->getRepository($aggregateType)->extractStreamId($anEventSourcedAggregateRoot);

        $hash = $this->getIdentityHash($aggregateType, $streamId);

        if (isset($this->identityMap[$hash])) {
            throw new RuntimeException(
                sprintf(
                    "Aggregate of type %s with AggregateId %s is already registered",
                    $aggregateType->toString(),
                    $streamId->toString()
                )
            );
        }

        $this->identityMap[$hash] = $anEventSourcedAggregateRoot;

        $argv = compact('anEventSourcedAggregateRoot', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * Detach an EventSourcedAggregateRoot
     *
     * It will be removed during commit
     *
     * @param object $anEventSourcedAggregateRoot
     * @throws Exception\InvalidArgumentException
     * @triggers detach.pre
     * @triggers detach.post
     */
    public function detach($anEventSourcedAggregateRoot)
    {
        if (!is_object($anEventSourcedAggregateRoot)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Given aggregate is not an object. It is of type %s',
                    gettype($anEventSourcedAggregateRoot)
                )
            );
        }

        $argv = compact('anEventSourcedAggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        $aggregateType = $this->getAggregateType($anEventSourcedAggregateRoot);
        $streamId = $this->getRepository($aggregateType)->extractStreamId($anEventSourcedAggregateRoot);


        $hash = $this->getIdentityHash($aggregateType, $streamId);

        if (isset($this->identityMap[$hash])) {
            unset($this->identityMap[$hash]);
        }

        $this->detachedAggregates[$hash] = $anEventSourcedAggregateRoot;

        $argv = compact('anEventSourcedAggregateRoot', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
    }
   
    /**
     * Load an EventSourcedAggregateRoot by it's AggregateType and name
     * 
     * @param AggregateType $aggregateType
     * @param StreamName $streamId
     * @triggers find.pre
     * @triggers find.post
     * @return object|null
     */        
    public function find(AggregateType $aggregateType, StreamName $streamId)
    {
        $hash = $this->getIdentityHash($aggregateType, $streamId);
        
        if (isset($this->identityMap[$hash])) {
            return $this->identityMap[$hash];
        }

        if (isset($this->detachedAggregates[$hash])) {
            return null;
        }

        $argv = compact('aggregateType', 'streamName', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $result = $this->getPersistenceEvents()->triggerUntil($event, function ($res) {
            return is_object($res);
        });

        $historyEvents = null;

        if ($result->stopped()) {
            $streamOrAggregate = $result->last();

            if ($streamOrAggregate instanceof Stream) {
                $historyEvents = $streamOrAggregate;
            } else {
                return $streamOrAggregate;
            }
        }

        if (is_null($historyEvents)) {
            $historyEvents = $this->adapter->loadStream($aggregateType, $streamId);
        }
        
        if (count($historyEvents->streamEvents()) === 0) {
            return null;
        }

        $aggregate = $this->getRepository($aggregateType)->constructAggregateFromHistory(
            $historyEvents
        );

        $this->attach($aggregate);

        $argv = compact('aggregateType', 'streamName', 'hash', 'aggregate');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        return $event->getParam('aggregate');
    }
    


    /**
     * @return EventManager
     */
    public function getPersistenceEvents()
    {
        if (is_null($this->persistenceEvents)) {
            $this->setPersistenceEvents(new EventManager());
        }

        return $this->persistenceEvents;
    }

    /**
     * @param EventManager $anEventManager
     */
    public function setPersistenceEvents(EventManager $anEventManager)
    {
        $anEventManager->setIdentifiers(array(
            'prooph_event_store',
            __CLASS__,
            get_called_class()
        ));

        $this->persistenceEvents = $anEventManager;
    }
    
    /**
     * Get hash to identify EventSourcedAggregateRoot in the IdentityMap
     * 
     * @param AggregateType $aggregateType
     * @param StreamName $streamId
     * 
     * @return string
     */
    protected function getIdentityHash(AggregateType $aggregateType, StreamName $streamId)
    {        
        return $aggregateType->toString() . '::' . $streamId->toString();
    }

    /**
     * @param mixed $anEventSourcedAggregateRoot
     * @return AggregateType
     */
    protected function getAggregateType($anEventSourcedAggregateRoot)
    {
        return ($anEventSourcedAggregateRoot instanceof AggregateTypeProviderInterface)?
            $anEventSourcedAggregateRoot->aggregateType() : new AggregateType(get_class($anEventSourcedAggregateRoot));
    }

    /**
     * @param Event $e
     * @return RepositoryInterface
     */
    public function checkAggregateSpecificRepository(Event $e)
    {
        $aggregateType = $e->getParam('aggregateType');

        if (isset($this->repositoryMap[$aggregateType->toString()])) {
            $repositoryFQCN = $this->repositoryMap[$aggregateType->toString()];

            return new $repositoryFQCN($this, $aggregateType);
        }
    }
}
