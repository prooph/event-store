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
use Prooph\EventStore\Mapping\AggregateTypeProviderInterface;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
use Prooph\EventStore\Repository\RepositoryInterface;
use Prooph\EventStore\Stream\AggregateType;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamId;
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
     * The EventSourcedAggregateRoot identity map
     * 
     * @var array[$aggregateHash => eventSourcedAggregateRoot]
     */
    protected $identityMap = array();

    /**
     * @var RepositoryInterface[aggregateType => repository]
     */
    protected $repositoryIdentityMap = array();

    /**
     * @var array
     */
    protected $detachedAggregates = array();
           
    /**
     * Map of AggregateTypes to $repositoryFQCNs
     * 
     * @var array 
     */
    protected $repositoryMap = array();
    
    /**
     * @var boolean
     */
    protected $inTransaction = false;

    /**
     * @var EventManager
     */
    protected $persistenceEvents;

    /**
     * Construct
     * 
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->adapter               = $config->getAdapter();
        $this->repositoryMap         = $config->getRepositoryMap();

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
     * Get responsible repository for given AggregateType
     * 
     * @param AggregateType $aggregateType
     *
     * @triggers getRepository.pre
     * @triggers getRepository.post
     *
     * @return RepositoryInterface
     */
    public function getRepository(AggregateType $aggregateType)
    {
        $hash = 'repository::' . $aggregateType->toString();

        if (isset($this->repositoryIdentityMap[$hash])) {
            return $this->repositoryIdentityMap[$hash];
        }

        $argv = compact('aggregateType');

        $argv['eventStore'] = $this;

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__, $this, $argv);

        $repository = null;

        $result = $this->getPersistenceEvents()->triggerUntil(
            $event,
            function ($res) {
                return $res instanceof RepositoryInterface;
            }
        );

        if ($result->stopped()) {
            if ($result->last() instanceof RepositoryInterface) {
                $repository = $result->last();
            }
        }
        
        $this->repositoryIdentityMap[$hash] = $repository;

        $argv = compact('aggregateType', 'hash', 'repository');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
        
        return $event->getParam('repository');
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
     * Load an EventSourcedAggregateRoot by it's AggregateType and id
     * 
     * @param AggregateType $aggregateType
     * @param StreamId $streamId
     * @triggers find.pre
     * @triggers find.post
     * @return object|null
     */        
    public function find(AggregateType $aggregateType, StreamId $streamId)
    {
        $hash = $this->getIdentityHash($aggregateType, $streamId);
        
        if (isset($this->identityMap[$hash])) {
            return $this->identityMap[$hash];
        }

        if (isset($this->detachedAggregates[$hash])) {
            return null;
        }

        $argv = compact('aggregateType', 'streamId', 'hash');

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

        $argv = compact('aggregateType', 'streamId', 'hash', 'aggregate');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        return $event->getParam('aggregate');
    }
    
    /**
     * Clear cached objects
     * 
     * @return void
     */
    public function clear()
    {
        $this->identityMap = array();
        $this->detachedAggregates = array();
        $this->repositoryIdentityMap = array();

        if ($this->inTransaction) {
            $this->rollback();
        }
    }
    
    /**
     * Begin transaction
     *
     * @triggers beginTransaction
     */
    public function beginTransaction()
    {
        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->beginTransaction();
        }
        
        $this->inTransaction = true;

        $this->getPersistenceEvents()->trigger(__FUNCTION__, $this);
    }
    
    /**
     * Commit transaction
     *
     * @triggers commit.pre providing the identityMap as param
     * @triggers persist.pre for each EventSourcedAggregateRoot
     * @triggers persist.post for each EventSourcedAggregateRoot
     * @triggers commit.post with all persisted Streams. Perfect event to attach a domain event dispatcher
     */
    public function commit()
    {
        $argv = array('identityMap' => $this->identityMap);

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new PreCommitEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        $persistedSteams = array();

        foreach ($this->identityMap as $hash => $object) {

            $aggregateType = $this->getAggregateType($object);

            $pendingEvents = $this->getRepository($aggregateType)->extractPendingStreamEvents($object);

            $streamId = $this->getRepository($aggregateType)->extractStreamId($object);

            $argv = array(
                'aggregateType' => $aggregateType,
                'aggregate' => $object,
                'streamId' => $streamId,
                'pendingEvents' => $pendingEvents,
                'hash' => $hash
            );

            $argv = $this->getPersistenceEvents()->prepareArgs($argv);

            $event = new Event('persist.pre', $this, $argv);

            $this->getPersistenceEvents()->trigger($event);

            if ($event->propagationIsStopped()) {
                continue;
            }

            $pendingEvents = $event->getParam('pendingEvents');

            if (count($pendingEvents)) {

                $stream = new Stream($aggregateType, $streamId, $pendingEvents);

                $this->adapter->addToExistingStream($stream);

                $argv = array(
                    'aggregate' => $object,
                    'streamId' => $streamId,
                    'persistedEvents' => $stream->streamEvents(),
                    'hash' => $hash
                );

                $argv = $this->getPersistenceEvents()->prepareArgs($argv);

                $event = new Event('persist.post', $this, $argv);

                $this->getPersistenceEvents()->trigger($event);

                $persistedSteams[] = $stream;
            }
        }

        foreach ($this->detachedAggregates as $detachedAggregate) {
            $aggregateType = $this->getAggregateType($detachedAggregate);

            $this->adapter->removeStream(
                $aggregateType,
                $this->getRepository($aggregateType)->extractStreamId($detachedAggregate)
            );
        }

        $this->detachedAggregates = array();

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->commit();
        }

        $this->inTransaction = false;

        $argv = array('persistedStreams' => $persistedSteams);

        $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new PostCommitEvent(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
    }
    
    /**
     * Rollback transaction
     *
     * @triggers rollback
     */
    public function rollback()
    {
        foreach ($this->identityMap as $object) {
            //clear all pending events by requesting them and throw them away
            $this->getRepository($this->getAggregateType($object))->extractPendingStreamEvents($object);
        }

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->rollback();
        }
        

        $this->inTransaction = false;

        $this->getPersistenceEvents()->trigger(__FUNCTION__, $this);
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

        $anEventManager->attach('getRepository', array($this, 'checkAggregateSpecificRepository'), 100);
        $anEventManager->attach('getRepository', array($this, 'throwNoRepositoryFoundException'), -1000);

        $this->persistenceEvents = $anEventManager;
    }
    
    /**
     * Get hash to identify EventSourcedAggregateRoot in the IdentityMap
     * 
     * @param AggregateType $aggregateType
     * @param StreamId $streamId
     * 
     * @return string
     */
    protected function getIdentityHash(AggregateType $aggregateType, StreamId $streamId)
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

    /**
     * @param Event $e
     * @throws \RuntimeException
     */
    public function throwNoRepositoryFoundException(Event $e)
    {
        throw new RuntimeException(
            sprintf(
                "No Repository found for AggregateType %s. You can use the prooph/event-sourcing library and register the \Prooph\EventSourcing\EventStoreFeature\ProophEventSourcingFeature if you do not have an own event-sourcing implementation for your aggregates",
                $e->getParam('aggregateType')->toString()
            )
        );
    }
}
