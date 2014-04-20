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
use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Mapping\AggregateRootDecorator;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
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
     * @var EventSourcedAggregateRoot[]
     */
    protected $identityMap = array();

    /**
     * @var RepositoryInterface[]
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
     * Get responsible repository for given Aggregate FQCN
     * 
     * @param string $aggregateFQCN
     *
     * @triggers getRepository.pre
     * @triggers getRepository.post
     *
     * @return RepositoryInterface
     */
    public function getRepository($aggregateFQCN)
    {
        $argv = compact('aggregateFQCN');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $result = $this->getPersistenceEvents()->triggerUntil(
            __FUNCTION__ . '.pre',
            $this,
            $argv,
            function ($res) {
                return $res instanceof RepositoryInterface;
            }
        );

        if ($result->stopped()) {
            if ($result->last() instanceof RepositoryInterface) {
                return $result->last();
            }
        }

        $hash = 'repository::' . $aggregateFQCN;
        
        if (isset($this->repositoryIdentityMap[$hash])) {
            return $this->repositoryIdentityMap[$hash];
        }
        
        $repositoryFQCN = (isset($this->repositoryMap[$aggregateFQCN]))?
            $this->repositoryMap[$aggregateFQCN]
            : 'Prooph\EventStore\Repository\EventSourcingRepository';
        
        $repository = new $repositoryFQCN($this, $aggregateFQCN);
        
        $this->repositoryIdentityMap[$hash] = $repository;

        $argv = compact('aggregateFQCN', 'hash', 'repository');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
        
        return $event->getParam('repository');
    }

    /**
     * Register given EventSourcedAggregateRoot in the identity map
     *
     * @param EventSourcedAggregateRoot $eventSourcedAggregateRoot
     *
     * @triggers attach.pre
     * @triggers attach.post
     * @throws Exception\RuntimeException If AggregateRoot is already attached
     * @return void
     */
    public function attach(EventSourcedAggregateRoot $eventSourcedAggregateRoot)
    {
        $argv = compact('eventSourcedAggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        $hash = $this->getIdentityHash(
            get_class($eventSourcedAggregateRoot),
            AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($eventSourcedAggregateRoot))
        );

        if (isset($this->identityMap[$hash])) {
            throw new RuntimeException(
                sprintf(
                    "Aggregate of type %s with AggregateId %s is already registered",
                    get_class($eventSourcedAggregateRoot),
                    AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($eventSourcedAggregateRoot))
                )
            );
        }

        $this->identityMap[$hash] = $eventSourcedAggregateRoot;

        $argv = compact('eventSourcedAggregateRoot', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
    }

    /**
     * Detach an EventSourcedAggregateRoot
     *
     * It will be removed during commit
     *
     * @param EventSourcedAggregateRoot $eventSourcedAggregateRoot
     * @triggers detach.pre
     * @triggers detach.post
     */
    public function detach(EventSourcedAggregateRoot $eventSourcedAggregateRoot)
    {
        $argv = compact('eventSourcedAggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        $hash = $this->getIdentityHash(
            get_class($eventSourcedAggregateRoot),
            AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($eventSourcedAggregateRoot))
        );

        if (isset($this->identityMap[$hash])) {
            unset($this->identityMap[$hash]);
        }

        $this->detachedAggregates[$hash] = $eventSourcedAggregateRoot;

        $argv = compact('eventSourcedAggregateRoot', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);
    }
   
    /**
     * Load an EventSourcedAggregateRoot by it's FQCN and id
     * 
     * @param string $aggregateFQCN
     * @param mixed  $aggregateId
     * @triggers find.pre
     * @triggers find.post
     * @return EventSourcedAggregateRoot|null
     */        
    public function find($aggregateFQCN, $aggregateId)
    {
        $hash = $this->getIdentityHash($aggregateFQCN, AggregateIdBuilder::toString($aggregateId));
        
        if (isset($this->identityMap[$hash])) {
            return $this->identityMap[$hash];
        }

        if (isset($this->detachedAggregates[$hash])) {
            return null;
        }

        $argv = compact('aggregateFQCN', 'aggregateId', 'hash');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $result = $this->getPersistenceEvents()->triggerUntil($event, function ($res) {
            return $res instanceof EventSourcedAggregateRoot;
        });

        if ($result->stopped()) {
            return $result->last();
        }
        
        $historyEvents = $this->adapter->loadStream($aggregateFQCN, AggregateIdBuilder::toString($aggregateId));
        
        if (count($historyEvents) === 0) {
            return null;
        }

        $aggregateRoot = $this->getAggregateRootDecorator()->fromHistory($aggregateFQCN, $aggregateId, $historyEvents);

        $this->attach($aggregateRoot);

        $argv = compact('aggregateFQCN', 'aggregateId', 'hash', 'aggregateRoot');

        $argv = $this->getPersistenceEvents()->prepareArgs($argv);

        $event = new Event(__FUNCTION__ . '.post', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        return $event->getParam('aggregateRoot');
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
            if ($object instanceof EventSourcedAggregateRoot) {

                $pendingEvents = $this->getAggregateRootDecorator()->extractPendingEvents($object);

                $aggregateId = $this->getAggregateRootDecorator()->getAggregateId($object);

                $argv = array(
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

                    $aggregateFQCN = get_class($object);

                    $this->adapter->addToStream(
                        $aggregateFQCN,
                        AggregateIdBuilder::toString($aggregateId),
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
        }

        foreach ($this->detachedAggregates as $detachedAggregate) {
            $this->adapter->removeStream(
                get_class($detachedAggregate),
                AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($detachedAggregate))
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
            if ($object instanceof EventSourcedAggregateRoot) {
                //clear all pending events
                $this->getAggregateRootDecorator()->extractPendingEvents($object);
            }
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
     * @return AggregateRootDecorator
     */
    protected function getAggregateRootDecorator()
    {
        if (is_null($this->aggregateRootDecorator)) {
            $this->aggregateRootDecorator = new AggregateRootDecorator();
        }

        return $this->aggregateRootDecorator;
    }

    /**
     * @param AggregateRootDecorator $anAggregateRootDecorator
     */
    protected function setAggregateRootDecorator(AggregateRootDecorator $anAggregateRootDecorator)
    {
        $this->aggregateRootDecorator = $anAggregateRootDecorator;
    }
}
