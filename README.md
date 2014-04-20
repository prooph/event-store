ProophEventStore
===================
PHP 5.4+ EventSourcing library.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)

##Features
- Event driven library [in progress]
- Full DDD support [in progress]
- Collection based repositories [in progress]
- Support for ValueObjects as AggregateRoot identifiers [done]
- Transaction handling [done]
- Optional snapshot strategies [not started]
- Optional concurrent logging [not started]
- Link your own event dispatcher and/or connect ProophServiceBus [not started]
- Replay of all events [not started]
- FeatureManager to ease customization [not started]
- Changeable storage adapter like
-- ZF2 Tablegateway [in progress]
-- doctrine dbal [not started]
-- mongoDB [not started]
-- geteventstore client [not started]

##ZF2 insight
The ProophEventStore is based on ZF2 components which offer a lot of customization options.

##Quick start
```php
namespace {
    require_once '../vendor/autoload.php';
}

namespace My\Model {

    use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
    use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
    use Rhumsaa\Uuid\Uuid;

    //EventSourcing means your AggregateRoots are not persisted directly
    //but all DomainEvents which occurs during a transaction
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
            //When the EventStore reconstructs an AggregateRoot
            //it does not call the constructor again
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
         * and each event handler method is named like the event (without namespace)
         * with the prefix "on" before the name
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
         * and each event handler method is named like the event (without namespace)
         * with the prefix "on" before the name
         *
         * @param UserNameChanged $event
         */
        protected function onUsernameChanged(UserNameChanged $event)
        {
            $this->name = $event->newUsername();
        }
    }

    //All DomainEvents have to be of type AggregateChangedEvent,
    //these are specific events including a version
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

    //All DomainEvents have to be of type AggregateChangedEvent,
    //these are specific events including a version
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
    //We set up a new EventStore with a Zf2EventStoreAdapter using a SQLite in memory db

    use My\Model\User;
    use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
    use Prooph\EventStore\Configuration\Configuration;
    use Prooph\EventStore\EventStore;
    use Prooph\EventStore\PersistenceEvent\PostCommitEvent;

    $options = array(
        'connection' => array(
            'driver' => 'Pdo_Sqlite',
            'database' => ':memory:'
        )
    );

    $config = new Configuration();

    $config->setAdapter(new Zf2EventStoreAdapter($options));

    $eventStore = new EventStore($config);

    //We use a feature of the Zf2EventStoreAdapter
    //to automatically create an event stream table for our User AggregateRoot
    //Normally you use this functionality in a set up or migration script
    $eventStore->getAdapter()->createSchema(array('My\Model\User'));

    //We attach a listener to capture all persisted events within a transaction
    $eventStore->getPersistenceEvents()->attach(
        'commit.post',
        function (PostCommitEvent $event) {
            foreach ($event->getPersistedEvents() as $persistedEvent) {

                echo sprintf(
                    "Event %s was persisted during last transaction<br>The related Aggregate is %s and the payload of the event is %s<br>",
                    get_class($persistedEvent),
                    (string)$persistedEvent->aggregateId(),
                    json_encode($persistedEvent->payload())
                );

            }
        }
    );

    //Start a new transaction to capture all DomainEvents
    $eventStore->beginTransaction();

    //Do some stuff with your Aggregates
    $user = new User('Alexander');

    $user->changeName('Alex');

    //Request a repository for your AggregateRoot
    $repository = $eventStore->getRepository('My\Model\User');

    //Add new user to the repository
    //The repositories behave like collections,
    //you do not need to call save or something like that
    //just add new AggregateRoots,
    //fetch them with $repository->get($aggregateId);
    //or remove them with $repository->remove($aggregate);

    $repository->add($user);

    //Persist all occurred AggregateChangedEvents, commit active transaction and trigger commit.post event
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




