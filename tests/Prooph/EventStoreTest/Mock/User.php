<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 18.04.14 - 00:04
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\EventSourcing\DomainEvent\AggregateChangedEvent;
use Prooph\EventSourcing\EventSourcedAggregateRoot;
use Rhumsaa\Uuid\Uuid;

/**
 * Class User
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class User extends EventSourcedAggregateRoot
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $aggregateId
     * @param AggregateChangedEvent[] $historyEvents
     * @return User
     */
    public static function fromHistory($aggregateId, array $historyEvents)
    {
        $selfREf = new \ReflectionClass(__CLASS__);

        $instance = $selfREf->newInstanceWithoutConstructor();

        $instance->initializeFromHistory($aggregateId, $historyEvents);

        return $instance;
    }

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $id = Uuid::uuid4()->toString();
        $this->apply(new UserCreated($id, array('id' => $id, 'name' => $name)));
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param string $newName
     */
    public function changeName($newName)
    {
        $this->apply(new UserNameChanged($this->id, array('username' => $newName)));
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param UserCreated $event
     */
    protected function onUserCreated(UserCreated $event)
    {
        $this->id = $event->userId();
        $this->name = $event->name();
    }

    /**
     * @param UserNameChanged $event
     */
    protected function onUsernameChanged(UserNameChanged $event)
    {
        $this->name = $event->newUsername();
    }

    /**
     * @return \Prooph\EventStore\EventSourcing\AggregateChangedEvent[]
     */
    public function accessPendingEvents()
    {
        return $this->getPendingEvents();
    }
}
 