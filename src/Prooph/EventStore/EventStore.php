<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore;

use Assert\Assertion;
use Prooph\EventStore\Adapter\AdapterInterface;
use Prooph\EventStore\Adapter\Feature\TransactionFeatureInterface;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
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
        foreach ($streamEvents as $streamEvent) {
            Assertion::isInstanceOf($streamEvent, 'Prooph\EventStore\Stream\StreamEvent');
        }

        $argv = array('streamName' => $aStreamName, 'streamEvents' => $streamEvents);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

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
     * @param null|int $minVersion
     * @throws Exception\StreamNotFoundException
     * @return Stream
     */
    public function load(StreamName $aStreamName, $minVersion = null)
    {
        $argv = array('streamName' => $aStreamName, 'minVersion' => $minVersion);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {

            $stream = $event->getParam('stream', false);

            if ($stream instanceof Stream && $stream->streamName()->toString() == $aStreamName->toString()) {
                return $stream;
            }

            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $aStreamName->toString()
                )
            );
        }

        $aStreamName = $event->getParam('streamName');

        $minVersion = $event->getParam('minVersion');

        $stream = $this->adapter->load($aStreamName, $minVersion);

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
     * @param null|int $minVersion
     * @return StreamEvent[]
     */
    public function loadEventsByMetadataFrom(StreamName $aStreamName, array $metadata, $minVersion = null)
    {
        $argv = array('streamName' => $aStreamName, 'metadata' => $metadata, 'minVersion' => $minVersion);

        $event = new Event(__FUNCTION__ . '.pre', $this, $argv);

        $this->getPersistenceEvents()->trigger($event);

        if ($event->propagationIsStopped()) {
            return $event->getParam('streamEvents', array());
        }

        $aStreamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');
        $minVersion = $event->getParam('minVersion');

        $events = $this->adapter->loadEventsByMetadataFrom($aStreamName, $metadata, $minVersion);

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
}
