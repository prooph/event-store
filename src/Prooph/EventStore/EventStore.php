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
use Prooph\EventStore\Adapter\Builder\AggregateIdBuilder;
use Prooph\EventStore\Adapter\Feature\TransactionFeatureInterface;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventSourcing\AggregateTypeProviderInterface;
use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Mapping\AggregateRootDecorator;
use Prooph\EventStore\Mapping\AggregateRootPrototypeManager;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
use Prooph\EventStore\Repository\EventSourcingRepository;
use Prooph\EventStore\Repository\RepositoryInterface;
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
     * @var EventSourcedAggregateRoot[$aggregateHash => eventSourcedAggregateRoot]
     */
    protected $identityMap = array();

    /**
     * @var RepositoryInterface[$aggregateType => repository]
     */
    protected $repositoryIdentityMap = array();

    /**
     * @var array
     */
    protected $detachedAggregates = array();
           
    /**
     * Map of $aggregateFQCNs to $repositoryFQCNs
     * 
     * @var array 
     */
    protected $repositoryMap = array();
    
    /**
     * @var boolean
     */
    protected $inTransaction = false;

    /**
     * @var AggregateRootDecorator
     */
    protected $aggregateRootDecorator;

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
     * @param string $aggregateType
     *
     * @triggers getRepository.pre
     * @triggers getRepository.post
     *
     * @return RepositoryInterface
     */
    public function getRepository($aggregateType)
    {
        $hash = 'repository::' . $aggregateType;

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
        $aggregateId = $this->getRepository($aggregateType)->extractAggregateIdAsString($anEventSourcedAggregateRoot);

        $hash = $this->getIdentityHash($aggregateType, $aggregateId);

        if (isset($this->identityMap[$hash])) {
            throw new RuntimeException(
                sprintf(
                    "Aggregate of type %s with AggregateId %s is already registered",
                    $aggregateType,
                    $aggregateId
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
        $aggregateId = $this->getRepository($aggregateType)->extractAggregateIdAsString($anEventSourcedAggregateRoot);


        $hash = $this->getIdentityHash($aggregateType, $aggregateId);

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
     * @param string $aggregateType
     * @param string $aggregateId
     * @triggers find.pre
     * @triggers find.post
     * @return object|null
     */        
    public function find($aggregateType, $aggregateId)
    {
        $hash = $this->getIdentityHash($aggregateType, $aggregateId);
        
        if (isset($this->identityMap[$hash])) {
            return $this->identityMap[$hash];
        }

        if (isset($this->detachedAggregates[$hash])) {
            return null;
        }

        $argv = compact('aggregateType', 'aggregateId', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $result = $this->getPersistenceEvents()->triggerUntil($event, function ($res) {
            return $res instanceof EventSourcedAggregateRoot;
        });

        if ($result->stopped()) {
            return $result->last();
        }
        
        $historyEvents = $this->adapter->loadStream($aggregateType, $aggregateId);
        
        if (count($historyEvents) === 0) {
            return null;
        }

        $eventSourcedAggregateRoot = $this->getRepository($aggregateType)->constructAggregateFromHistory(
            $aggregateType,
            $aggregateId,
            $historyEvents
        );

        $this->attach($eventSourcedAggregateRoot);

        $argv = compact('aggregateType', 'aggregateId', 'hash', 'eventSourcedAggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        return $event->getParam('eventSourcedAggregateRoot');
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
     * @triggers commit.post with all persisted events. Perfect event to attach a domain event dispatcher
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

        $allPendingEvents = array();

        foreach ($this->identityMap as $hash => $object) {

            $aggregateType = $this->getAggregateType($object);

            $pendingEvents = $this->getRepository($aggregateType)->extractPendingEvents($object);

            $aggregateId = $this->getRepository($aggregateType)->extractAggregateIdAsString($object);

            $argv = array(
                'aggregateType' => $aggregateType,
                'aggregate' => $object,
                'aggregateId' => $aggregateId,
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

                $this->adapter->addToStream(
                    $aggregateType,
                    $aggregateId,
                    $pendingEvents
                );

                $argv = array(
                    'aggregate' => $object,
                    'aggregateId' => $aggregateId,
                    'persistedEvents' => $pendingEvents,
                    'hash' => $hash
                );

                $argv = $this->getPersistenceEvents()->prepareArgs($argv);

                $event = new Event('persist.post', $this, $argv);

                $this->getPersistenceEvents()->trigger($event);

                $allPendingEvents += $event->getParam('persistedEvents');
            }
        }

        foreach ($this->detachedAggregates as $detachedAggregate) {
            $aggregateType = $this->getAggregateType($detachedAggregate);

            $this->adapter->removeStream(
                $aggregateType,
                $this->getRepository($aggregateType)->extractAggregateIdAsString($detachedAggregate)
            );
        }

        $this->detachedAggregates = array();

        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->commit();
        }

        $this->inTransaction = false;

        $argv = array('persistedEvents' => $allPendingEvents);

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
            $this->getRepository($this->getAggregateType($object))->extractPendingEvents($object);
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
        $anEventManager->attach('getRepository', array($this, 'provideDefaultRepository'), -100);

        $this->persistenceEvents = $anEventManager;
    }
    
    /**
     * Get hash to identify EventSourcedAggregateRoot in the IdentityMap
     * 
     * @param string $sourceFQCN
     * @param string $sourceId
     * 
     * @return string
     */
    protected function getIdentityHash($sourceFQCN, $sourceId)
    {        
        return $sourceFQCN . '::' . $sourceId;
    }

    /**
     * @param mixed $anEventSourcedAggregateRoot
     * @return string
     */
    protected function getAggregateType($anEventSourcedAggregateRoot)
    {
        return ($anEventSourcedAggregateRoot instanceof AggregateTypeProviderInterface)?
            $anEventSourcedAggregateRoot->aggregateType() : get_class($anEventSourcedAggregateRoot);
    }

    /**
     * @param Event $e
     * @return RepositoryInterface
     */
    public function checkAggregateSpecificRepository(Event $e)
    {
        $aggregateType = $e->getParam('aggregateType');

        if (isset($this->repositoryMap[$aggregateType])) {
            $repositoryFQCN = $this->repositoryMap[$aggregateType];

            return new $repositoryFQCN($this, $aggregateType);
        }
    }

    /**
     * @param Event $e
     * @return \Prooph\EventStore\Repository\EventSourcingRepository
     */
    public function provideDefaultRepository(Event $e)
    {
        return new EventSourcingRepository($this, $e->getParam('aggregateType'));
    }
}
