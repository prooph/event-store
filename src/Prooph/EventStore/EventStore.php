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
use Prooph\EventStore\Repository\RepositoryInterface;

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
     * @var array
     */
    protected $identityMap = array();

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
     * Construct
     * 
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->adapter               = $config->getAdapter();
        $this->repositoryMap         = $config->getRepositoryMap();
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
     * @return RepositoryInterface
     */
    public function getRepository($aggregateFQCN)
    {
        $hash = 'repository::' . $aggregateFQCN;
        
        if (isset($this->identityMap[$hash])) {
            return $this->identityMap[$hash];
        }
        
        $repositoryFQCN = (isset($this->repositoryMap[$aggregateFQCN]))?
            $this->repositoryMap[$aggregateFQCN]
            : 'Prooph\EventStore\Repository\EventSourcingRepository';
        
        $repository = new $repositoryFQCN($this, $aggregateFQCN);
        
        $this->identityMap[$hash] = $repository;
        
        return $repository;
    }

    /**
     * Register given EventSourcedAggregateRoot in the identity map
     *
     * @param EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     *
     * @throws Exception\RuntimeException If AggregateRoot is already attached
     * @return void
     */
    public function attach(EventSourcedAggregateRoot $anEventSourcedAggregateRoot)
    {
        //@TODO: trigger pre attach event
        $hash = $this->getIdentityHash(
            get_class($anEventSourcedAggregateRoot),
            AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($anEventSourcedAggregateRoot))
        );

        if (isset($this->identityMap[$hash])) {
            throw new RuntimeException(
                sprintf(
                    "Aggregate of type %s with AggregateId %s is already registered",
                    get_class($anEventSourcedAggregateRoot),
                    AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($anEventSourcedAggregateRoot))
                )
            );
        }

        $this->identityMap[$hash] = $anEventSourcedAggregateRoot;

        //@TODO: trigger post attach event
    }

    /**
     * Detach an EventSourcedAggregateRoot
     *
     * It will be removed during commit
     *
     * @param EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     */
    public function detach(EventSourcedAggregateRoot $anEventSourcedAggregateRoot)
    {
        //@TODO: trigger pre detach

        $hash = $this->getIdentityHash(
            get_class($anEventSourcedAggregateRoot),
            AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($anEventSourcedAggregateRoot))
        );

        if (isset($this->identityMap[$hash])) {
            unset($this->identityMap[$hash]);
        }

        $this->detachedAggregates[$hash] = $anEventSourcedAggregateRoot;

        //@TODO: trigger post detach
    }
   
    /**
     * Load an EventSourcedAggregateRoot by it's FQCN and id
     * 
     * @param string $aggregateFQCN
     * @param mixed  $aggregateId
     * 
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
        
        //@TODO: trigger pre find event with eventstore and check if result->last() is EventSourcedAggregateRoot,
        //@TODO: in that case the snapshotFeature has triggered the loading with last snapshot version
        
        $historyEvents = $this->adapter->loadStream($aggregateFQCN, AggregateIdBuilder::toString($aggregateId));
        
        if (count($historyEvents) === 0) {
            return null;
        }

        $aggregateRoot = $this->getAggregateRootDecorator()->fromHistory($aggregateFQCN, $aggregateId, $historyEvents);

        $this->attach($aggregateRoot);

        //@todo trigger post find event with $aggregateRoot and $aggregateRootId and eventstore

        return $aggregateRoot;
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

        if ($this->inTransaction) {
            $this->rollback();
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        //@TODO: trigger pre begin transaction event
        if ($this->adapter instanceof TransactionFeatureInterface) {
            $this->adapter->beginTransaction();
        }
        
        $this->inTransaction = true;

        //@TODO: trigger post begin transaction event
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        //@TODO: trigger pre commit event with eventstore and identityMap

        $allPendingEvents = array();

        foreach ($this->identityMap as $hash => $object) {
            if ($object instanceof EventSourcedAggregateRoot) {

                //@TODO: trigger pre persist event with eventstore pending events and aggregate and aggregateId

                $pendingEvents = $this->getAggregateRootDecorator()->extractPendingEvents($object);

                if (count($pendingEvents)) {

                    $aggregateFQCN = get_class($object);

                    $this->adapter->addToStream(
                        $aggregateFQCN,
                        AggregateIdBuilder::toString($this->getAggregateRootDecorator()->getAggregateId($object)),
                        $pendingEvents
                    );

                    //@TODO: trigger post persist event with pending events and aggregate and aggregateId

                    $allPendingEvents += $pendingEvents;
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

        //@TODO: trigger post commit event with eventstore and $allPendingEvents
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        //@TODO: trigger pre rollback event
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

        //@TODO: trigger post rollback event
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
