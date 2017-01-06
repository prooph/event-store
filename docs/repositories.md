# Working with Repositories

Repositories typically connect your domain model with the persistence layer (part of the infrastructure).
Following DDD suggestions your domain model should be database agnostic.
An event store is of course some kind of database so you are likely looking for a third-party event store that gets out of your way.

*The good news is:* **You've found one!**

But you need to get familiar with the concept. So you're pleased to read this document and follow the example.
Afterwards you should be able to integrate `prooph/event-store` into your infrastructure without coupling it with your model.

## Event Sourced Aggregates

We assume that you want to work with event sourced aggregates. If you are not sure what we are talking about
please refer to the great educational project [Buttercup.Protects](http://buttercup-php.github.io/protects/) by Mathias Verraes.
prooph/event-store does not include base classes or traits to add event sourced capabilities to your aggregates.

Sounds bad? It isn't!

It is your job to write something like `Buttercup.Protects` for your model. Don't be lazy in this case.
If you just want to play with the idea you can have a look at [prooph/event-sourcing](https://github.com/prooph/event-sourcing).
It is our internal event sourcing package and ships with support for prooph/event-store.

The event store doesn't know anything about aggregates. It is just interested in `Prooph\Common\Messaging\Message events`.
These events are organized in `Prooph\EventStore\Stream\Stream`s.
A repository is responsible for extracting pending events from aggregates and putting them in the correct stream.
And the repository must also be able to load persisted events from a stream and reconstitute an aggregate.
To provide this functionality the repository makes use of various helper classes explained below.

## AggregateType
Each repository is responsible for one `Prooph\EventStore\Aggregate\AggregateType`. Super types are not supported.
Imagine we have a domain with `Admin extends User` and `Employee extends User`. You'd need to have a `AdminRepository` and
a `EmployeeRepository` in this case. If this is not what you want you can create a custom aggregate translator (see below)
which is capable of reconstituting the correct types based on information derived from persisted domain events.
Then you can have a `UserRepository` set up with your custom aggregate translator and it should work.

## AggregateTranslator

To achieve 100% decoupling between layers and/or contexts you can make use of translation adapters.
For prooph/event-store such a translation adapter is called an `Prooph\EventStore\Aggregate\AggregateTranslator`.

The interface requires you to implement 5 methods:

- extractAggregateId
- extractAggregateVersion
- extractPendingStreamEvents
- reconstituteAggregateFromHistory
- applyStreamEvents

To make your life easier prooph/event-store ships with a `Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator` which implements the interface.

Let's have a look at the constructor

```php
/**
 * @param null|string   $identifierMethodName
 * @param null|string   $versionMethodName
 * @param null|string   $popRecordedEventsMethodName
 * @param null|string   $replayEventsMethodsName
 * @param null|string   $staticReconstituteFromHistoryMethodName
 * @param null|callable $eventToMessageCallback
 * @param null|callable $messageToEventCallback
 */
public function __construct(
    $identifierMethodName = null,
    $versionMethodName = null,
    $popRecordedEventsMethodName = null,
    $replayEventsMethodsName = null,
    $staticReconstituteFromHistoryMethodName = null,
    $eventToMessageCallback = null,
    $messageToEventCallback = null)
{
    //...
}
```

We can identify 7 dependencies but all are optional.

- `$identifierMethodName`
  - defaults to `getId`
  - used to `extractAggregateId` and must return a string
  - you can have a translator per aggregate type, so if you prefer to have methods reflecting domain language you likely want to use methods like `getTrackingId`, `getProductNumber`, etc.. As you can see, this is no problem for the event store. Feel free to model your aggregates exactly the way you need it!
- `$versionMethodName`
  - defaults to `getVersion`
  - used to `extractVersion` of the aggregate root
- `$popRecordedEventsMethodName`
  - defaults to `popRecordedEvents`
  - with this method the `ConfigurableAggregateTranslator` requests the latest recorded events from your aggregate
  - the aggregate should also clear its internal event cache before returning the events as no additional method is invoked
- `replayStreamEvents`
  - defaults to `replay`
  - used in case the repository loaded a snapshot and needs to replay newer events
- `$staticReconstituteFromHistoryMethodName`
  - defaults to `reconstituteFromHistory`
  - like indicated in the parameter name the referenced method must be static (a named constructor) which must return an instance of the aggregate with all events replayed
- `$eventToMessageCallback`
  - completely optional
  - you can pass any callable
  - the callable is invoked for each domain event returned by `$popRecordedEventsMethodName` and can be used to translate a domain event into a `Prooph\Common\Messaging\Message`
  - the message interface is required by the event store adapters to function correctly
  - you can also decide to let your domain events implement the interface. This would make your life easier when you want to make use of advanced features provided by prooph. But, again. Your domain events don't have to implement the interface. It is your choice!
- `$messageToEventCallback`
  - completely optional
  - it is the opposite of `$eventToMessageCallback`
  - when you pass a callable it is invoked for each message (loaded from the event store) before `$staticReconstituteFromHistoryMethodName` or `$applyEventsMethodsName`is called

Alternatively you may use `AggregateTranslatorConfiguration` if you don't like the constructor based configuration.
`AggregateTranslatorConfiguration` is implemented immutable and has a fluid interface

```php
$config = AggregateTranslatorConfiguration::createWithDefaults();
$config = $config
    ->withIdentifierMethodName('id')
    ->withVersionMethodName('version'); 
    
$translator = ConfigurableAggregateTranslator::fromConfiguration($config);    
```

*Note: When using the translation callbacks shown above you should consider translating domain events into `Prooph\Common\Messaging\DomainEvent` objects. It is a default implementation of the `Message` interface and all event store adapters can handle it out-of-the-box.
If you decide to provide your own implementation of `Prooph\Common\Messaging\Message` you should have a look at `Prooph\Common\Messaging\MessageFactory` and `Prooph\Common\Messaging\MessageConverter` because the event store adapters work with these to translate events into PHP arrays and back.*

## Snapshot Store

A repository can be set up with a snapshot store to speed up loading of aggregates.
Checkout the snapshot docs for more information.

## Event Streams

An event stream can be compared with a table in a relational database (and in case of the doctrine adapter it is a table).
By default the repository puts all events of all aggregates (no matter the type) in a single stream called **event_stream**.
If you wish to use another name, you can pass a custom `Prooph\EventStore\Stream\StreamName` to the repository.
This is especially useful when you want to have an event stream per aggregate type, for example store all user related events
in a `user_stream`.

The repository can also be configured to create a new stream for each new aggregate instance. You need to turn the last
constructor parameter `oneStreamPerAggregate` to true to enable the mode.
This can be useful when working for example with MongoDB and you want to persist all events of an aggregate in single document (take care of the document size limit).
If the mode is enabled the repository builds a unique stream name for each aggregate by using the `AggregateType` and append
the `aggregateId` of the aggregate. The stream name for a new `Acme\User` with id `123` would look like this: `Acme\User-123`.

Depending on the event store adapter used the stream name is maybe modified by the adapter to replace or removed non supported characters.
Check your adapter of choice for details. You can also override `AggregateRepository::determineStreamName` to apply a custom logic
for building the stream name.

## Wiring It Together
Best way to see a repository in action is by looking at the `ProophTest\EventStore\Aggregate\AggregateRepositoryTest`.

### Set Up

```php
$this->repository = new AggregateRepository(
    $this->eventStore,
    AggregateType::fromAggregateRootClass('ProophTest\EventStore\Mock\User'),
    new ConfigurableAggregateTranslator()
);

$this->eventStore->beginTransaction();

$this->eventStore->create(new Stream(new StreamName('event_stream'), []));

$this->eventStore->commit();
```

Notice the injected dependencies! Snapshot store, stream name and stream mode are optional and not injected for all tests.
Therefor stream name defaults to **event_stream** and the repository appends all events to this stream.
For the test cases we also create the stream on every run. In a real application you need to do this only once.

```php
/**
 * @test
 */
public function it_adds_a_new_aggregate()
{
    $this->eventStore->beginTransaction();

    $user = User::create('John Doe', 'contact@prooph.de');

    $this->repository->addAggregateRoot($user);

    $this->eventStore->commit();

    $fetchedUser = $this->repository->getAggregateRoot(
        $user->getId()->toString()
    );

    $this->assertInstanceOf('ProophTest\EventStore\Mock\User', $fetchedUser);

    $this->assertNotSame($user, $fetchedUser);

    $this->assertEquals('John Doe', $fetchedUser->name());

    $this->assertEquals('contact@prooph.de', $fetchedUser->email());
}
```

In the first test case you can see how an aggregate (the user entity in this case) can be added to the repository.

```php
/**
 * @test
 */
public function it_tracks_changes_of_aggregate()
{
    $this->eventStore->beginTransaction();

    $user = User::create('John Doe', 'contact@prooph.de');

    $this->repository->addAggregateRoot($user);

    $this->eventStore->commit();

    $this->eventStore->beginTransaction();

    $fetchedUser = $this->repository->getAggregateRoot(
        $user->getId()->toString()
    );

    $this->assertNotSame($user, $fetchedUser);

    $fetchedUser->changeName('Max Mustermann');

    $this->eventStore->commit();

    $fetchedUser2 = $this->repository->getAggregateRoot(
        $user->getId()->toString()
    );

    $this->assertNotSame($fetchedUser, $fetchedUser2);

    $this->assertEquals('Max Mustermann', $fetchedUser2->name());
}
```

Here we first add the user, then load it with the help of the repository and finally we change the user entity.
The change causes a `UserNameChanged` event. You may noticed that after changing the user name, the user is not passed to
a repository method. Only `$this->eventStore->commit();` is called. But as you can see in the last test assertion the username
is changed and the appropriate domain event was added to the `event_stream`. This happens becasue the repository manages an identity map
internally. Each aggregate root loaded via `AggregateRepository::getAggregateRoot` is added to the identity map and
new events recorded by such an agggregate root are added automatically to the event stream on `EventStore::commit`.

**But** the identity map is cleared after each transaction commit. You may noticed the `assertNotSame` checks in the test.
The repository keeps an aggregate only in memory as long as the transaction is active. Otherwise multiple long-running
processes dealing with the same aggregate would run into concurrency issues very fast.

The test case has some more tests including snapshot usage and working with different stream names / strategies.
Just browse through the test methods for details.


