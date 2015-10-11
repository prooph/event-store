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
prooph/event-store does not include base classes or traits to add event sourced capabilities to your entities.

Sounds bad? It isn't!

It is your job to write something like `Buttercup.Protects` for your model. Don't be lazy in this case.
If you just want to play with the idea you can have a look at [prooph/event-sourcing](https://github.com/prooph/event-sourcing).
It is our internal event sourcing package and ships with support for prooph/event-store.

## AggregateTranslator

To achieve 100% decoupling between layers and/or contexts you can make use of translation adapters.
For prooph/event-store such a translation adapter is called an [AggregateTranslator](../src/Aggregate/AggregateTranslator.php).

The interface requires you to implement three methods:

- extractAggregateId
- extractPendingStreamEvents
- reconstituteAggregateFromHistory

That is all a repository needs to handle your event sourced aggregates. But to make it even more simple to get started
prooph/event-store ships with a [ConfigurableAggregateTranslator](../src/Aggregate/ConfigurableAggregateTranslator.php) which implements the interface.

Let's have a look at the constructor

```php
/**
 * @param null|string   $identifierMethodName
 * @param null|string   $popRecordedEventsMethodName
 * @param null|string   $staticReconstituteFromHistoryMethodName
 * @param null|callable $eventToMessageCallback
 * @param null|callable $messageToEventCallback
 */
public function __construct(
    $identifierMethodName = null,
    $popRecordedEventsMethodName = null,
    $staticReconstituteFromHistoryMethodName = null,
    $eventToMessageCallback = null,
    $messageToEventCallback = null)
{
    //...
}
```

We can identify 5 dependencies but all are optional.

- `$identifierMethodName`
  - defaults to `getId`
  - it is used to `AggregateTranslator::extractAggregateId` and must return a string
  - you can have a translator per aggregate type, so if you prefer to have methods reflecting domain language you likely want to use methods like `getTrackingId`, `getProductNumber`, etc.. As you can see, this is no problem for the event store. Feel free to model your aggregates exactly the way you need it!
- `$popRecordedEventsMethodName`
  - defaults to `popRecordedEvents`
  - with this method the `ConfigurableAggregateTranslator` requests the latest recorded events from your aggregate
  - the aggregate should also clear the event cache before returning the events
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
  - when you pass a callable it is invoked for each message (loaded from the event store) before `$staticReconstituteFromHistoryMethodName` is called


*Note: When using the translation callbacks shown above you should consider translating domain events into `Prooph\Common\Messaging\DomainEvent` objects. It is a default implementation of the `Message` interface and all event store adapters can handle it out-of-the-box.
If you decide to provide your own implementation of `Prooph\Common\Messaging\Message` you should have a look at `Prooph\Common\Messaging\MessageFactory` and `Prooph\Common\Messaging\MessageConverter` because the event store adapters work with these to translate events into PHP arrays and back.*

## Stream Strategies

Besides the `AggregateTranslator` prooph/event-store offers a second customization option. With stream strategies you can control
how the event store should organize the event streams. The default behaviour of the event store is to store all events in a single stream.
But depending on the amount of events and the adapter used you may want to have a stream per aggregate instance or a stream per aggregate type.
See the list below:

- [SingleStreamStrategy](../src/Stream/SingleStreamStrategy.php): Stores the events of all aggregates in one single stream
- [AggregateStreamStrategy](../src/Stream/AggregateStreamStrategy.php): Creates a stream for each aggregate instance
- [AggregateTypeStreamStrategy](../src/Stream/AggregateTypeStreamStrategy.php): Stores the events of all aggregates of the same type (f.e. all Users) in one stream

## Wiring It Together

Now that you know the customization options you may ask: **How to put all that together?**
The answer is: **With a repository!**
The best way to see it in action is by looking at the [AggregateRepositoryTest](../tests/Aggregate/AggregateRepositoryTest.php).

### Set Up

```php
$this->repository = new AggregateRepository(
    $this->eventStore,
    AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
    new ConfigurableAggregateTranslator()
);

$this->eventStore->beginTransaction();

$this->eventStore->create(new Stream(new StreamName('event_stream'), []));

$this->eventStore->commit();
```

Notice the injected dependencies! A stream strategy is not injected. This would be the fourth parameter of the repository constructor.
It is optional and defaults to `SingleStreamStrategy` using `event_stream` as the stream name.
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

    $this->repository->clearIdentityMap();

    $fetchedUser = $this->repository->getAggregateRoot(
        $user->getId()->toString()
    );

    $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $user);

    $this->assertNotSame($user, $fetchedUser);

    $this->assertEquals('John Doe', $fetchedUser->name());

    $this->assertEquals('contact@prooph.de', $fetchedUser->email());
}
```

In the first tes case you can see how an aggregate (the user entity in this case) can be added to the repository.
Under the hood the `ConfigurableAggregateTranslator` and the `SingleStreamStrategy` do their jobs so that the
recorded domain events of the `user aggregate root` are stored in the `event_stream`.

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

    $this->assertSame($user, $fetchedUser);

    $fetchedUser->changeName('Max Mustermann');

    $this->eventStore->commit();

    $this->repository->clearIdentityMap();

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
