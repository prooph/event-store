ProophEventStore
===================
PHP 5.4+ EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)

The library is heavily inspired by [malocher/event-store](https://github.com/malocher/event-store), [beberlei/litecqrs-php](https://github.com/beberlei/litecqrs-php) and [szjani/predaddy](https://github.com/szjani/predaddy)

##Features
- Event-driven library [in progress]
- Full DDD support (no dependency of ES in domain model) [done]
- Default event-sourcing components [done] -> but live in a separate library: [prooph/event-sourcing](https://github.com/prooph/event-sourcing)
- IdentityMap to support collection-oriented repositories [done]
- Transaction handling [done]
- Optional snapshot strategies [not started]
- Optional concurrent logging [not started]
- Link your own event dispatcher and/or connect ProophServiceBus [done]
- FeatureManager to ease customization [done]
- Support for [Buttercup.Protects](https://github.com/buttercup-php/protects) [not started]
- Changeable storage adapter like
    - ZF2 Tablegateway [done]
    - doctrine dbal [not started]
    - mongoDB [not started]
    - geteventstore client [not started]

##Used Third-Party Libraries
- The ProophEventStore is based on [ZF2 components](http://framework.zend.com/) which offer a lot of customization options.
- Uuids of the StreamEvents are generated with [rhumsaa/uuid](https://github.com/ramsey/uuid)
- Assertions are performed by [beberlei/assert](https://github.com/beberlei/assert)

##Installation
You can install ProophEventStore via composer by adding `"prooph/event-store": "dev-master"` as requirement to your composer.json.


##Quick start
```php
<?php
/*
 * This file is part of the codeliner/event-store.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.04.14 - 21:44
 */

/**
 * This Quick Start uses the ProophEventStore together with ProophEventSourcing.
 * ProophEventSourcing is an event-sourcing library which seamlessly
 * integrates with ProophEventStore.
 *
 * With the help of an EventStoreFeature shipped with ProophEventSourcing
 * you can connect EventSourcedAggregateRoots with the EventStore
 * @see configuration of the EventStore near the line 176 of this file.
 *
 * Why is ProophEventSourcing not included in the library?
 *
 * Well, the answer is quite easy. Normally you do not want to couple
 * your domain model with the infrastructure (which is definitely the
 * right place for an EventStore) so the ProophEventStore does not force you
 * to use specific event-sourcing interfaces or DomainEvent implementations.
 *
 * It is up to you if you use a library like ProophEventSourcing,
 * Buttercup.Protects or provide your own implementation for your domain model.
 * The only thing you need to do is, tell the EventStore which type of
 * repository it should use. The EventStore defines it's own RepositoryInterface
 * (Prooph\EventStore\Repository\RepositoryInterface)
 * that you need to implement if you do not use ProophEventSourcing
 * which ships with a ready to use repository implementation.
 */
 
 
/**
 * tl;dr
 *
 * Assume, we have the following requirements in the composer.json
 *
 * "require": {
 * "php": ">=5.4",
 *   "prooph/event-store" : "dev-master",
 *   "prooph/event-sourcing": "dev-master" //Default event-sourcing library
 * },
 */
namespace {
    require_once '../vendor/autoload.php';
}

namespace My\Model {

    use Prooph\EventSourcing\DomainEvent\AggregateChangedEvent;
    use Prooph\EventSourcing\EventSourcedAggregateRoot;
    use Rhumsaa\Uuid\Uuid;

    //EventSourcing means your AggregateRoots are not persisted directly but all
    //DomainEvents which occurs during a transaction
    //Your AggregateRoots become EventSourcedAggregateRoots
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
         * @param string $name of the User
         */
        public function __construct($name)
        {
            //Construct is only called once.
            //When the EventStore reconstructs an AggregateRoot it does not call the constructor again
            $id = Uuid::uuid4()->toString();

            //Validation must always be done before creating any events.
            //Events should only contain valid information
            \Assert\that($name)->notEmpty()->string();

            //We do not set id and name directly but apply a new UserCreated event
            $this->apply(new UserCreated($id, array('name' => $name)));
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
            //Validation must always be done before creating any events.
            //Events should only contain valid information
            \Assert\that($newName)->notEmpty()->string();

            //Also this time we do not set the new name
            //but apply a UserNameChanged event with the new name
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
         * EventHandler for the UserCreated event
         *
         * By default the system assumes that the AggregateRoot
         * has one event handler method per event
         * and each event handler method is named like the event
         * (without namespace) with the prefix "on" before the name
         *
         * @param UserCreated $event
         */
        protected function onUserCreated(UserCreated $event)
        {
            //No validation here, just apply the values from given event
            $this->id = $event->userId();
            $this->name = $event->name();
        }

        /**
         * EventHandler for the UserNameChanged event
         *
         * By default the system assumes that the AggregateRoot
         * has one event handler method per event
         * and each event handler method is named like the event
         * (without namespace) with the prefix "on" before the name
         *
         * @param UserNameChanged $event
         */
        protected function onUsernameChanged(UserNameChanged $event)
        {
            $this->name = $event->newUsername();
        }
    }

    //All DomainEvents have to be of type AggregateChangedEvent
    //(When using ProophEventSourcing),
    //These are specific events including a version
    //and the related AggregateId
    class UserCreated extends AggregateChangedEvent
    {
        /**
         * @return string
         */
        public function userId()
        {
            return $this->aggregateId();
        }

        /**
         * @return string
         */
        public function name()
        {
            return $this->toPayloadReader()->stringValue('name');
        }
    }

    //All DomainEvents have to be of type AggregateChangedEvent
    //(When using ProophEventSourcing),
    //These are specific events including a version
    //and the related AggregateId
    class UserNameChanged extends AggregateChangedEvent
    {
        /**
         * @return string
         */
        public function newUsername()
        {
            return $this->toPayloadReader()->stringValue('username');
        }
    }
}

namespace {

    use My\Model\User;
    use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
    use Prooph\EventStore\Configuration\Configuration;
    use Prooph\EventStore\EventStore;
    use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
    use Prooph\EventStore\Stream\AggregateType;

    $config = new Configuration(array(
        //We set up a new EventStore with a Zf2EventStoreAdapter
        //using a SQLite in memory db ...
        'adapter' => array(
            'Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter' => array(
                'connection' => array(
                    'driver' => 'Pdo_Sqlite',
                    'database' => ':memory:'
                )
            )
        ),
        //... and register the ProophEventSourcingFeature
        //to connect the EventStore with our EventSourcedAggregateRoots
        'features' => array(
            'ProophEventSourcingFeature'
        ),
        //Features are loaded by a special ZF2 PluginManager
        //which can be configured like all other ZF2 ServiceManagers
        'feature_manager' => array(
            'invokables' => array(
                'ProophEventSourcingFeature' => 'Prooph\EventSourcing\EventStoreFeature\ProophEventSourcingFeature'
            )
        )
    ));

    $eventStore = new EventStore($config);

    //We use a feature of the Zf2EventStoreAdapter to automatically create an
    //event stream table for our User AggregateRoot
    //Normally you use this functionality in a set up or migration script
    $eventStore->getAdapter()->createSchema(array('My\Model\User'));

    //We attach a listener to capture all persisted streams within a transaction
    $eventStore->getPersistenceEvents()->attach(
        'commit.post',
        function (PostCommitEvent $event) {
            foreach ($event->getPersistedStreams() as $persistedStream) {

                foreach ($persistedStream->streamEvents() as $persistedStreamEvent) {
                    echo sprintf(
                        "Event %s was persisted during last transaction<br>The related Aggregate is %s and the payload of the event is %s<br>",
                        $persistedStreamEvent->eventName()->toString(),
                        (string)$persistedStream->streamId()->toString(),
                        json_encode($persistedStreamEvent->payload())
                    );
                }

            }
        }
    );

    //Start a new transaction to capture all DomainEvents
    $eventStore->beginTransaction();

    //Do some stuff with your Aggregates
    $user = new User('Alexander');

    $user->changeName('Alex');

    //Request a repository for your AggregateRoot
    $repository = $eventStore->getRepository(new AggregateType('My\Model\User'));

    /*
     * Normally you want to define a repository interface in your domain
     * with methods like add, get and remove and type hints that force you
     * to pass in objects related to the repository.
     *
     * Therefor the default methods of the ProophEventSourcing repository are named with a suffix
     * to avoid naming conflicts with your interfaces
     *
     * Add new user to the repository
     * The repositories behave like collections, you do not need to call save or something like that
     * just add new AggregateRoots,
     * fetch them with $repository->getFromStore($aggregateId);
     * or remove them with $repository->removeFromStore($aggregate);
     */
    $repository->addToStore($user);

    //Persist all occurred DomainEvents, commit active transaction and trigger a commit.post LifeCycleEvent
    $eventStore->commit();

    //Output should be somehting like:
    //
    //Event My\Model\UserCreated was persisted during last transaction
    //The related Aggregate is dda1f14e-0b27-455f-ac4e-fc0043fe8455 and the payload of the event is {"name":"Alexander"}
    //Event My\Model\UserNameChanged was persisted during last transaction
    //The related Aggregate is dda1f14e-0b27-455f-ac4e-fc0043fe8455 and the payload of the event is {"username":"Alex"}
}
```

##Contribution
coming soon ...

##Wiki
coming soon ...




