# Projections

New in v7 are queries and projectons.

## Queries

We are talking here about event store queries, not queries on your read model. An event store query reads one or
multiple event stream, aggregates some state from it and makes it accessible. A query is non-persistent, will only
get executed once, return a result, and that's it.

To get started, let's take a simple example where we want to query the
event-store, how often a given user has changed his username.

```php
$query = $projectionManager->createQuery();
$query
    ->init(function (): array {
        return ['count' => 0];
    })
    ->fromStream('user-123')
    ->when([
        'user-name-changed' => function (
            array $state, UsernameChanged $event
        ): array {
            $state['count']++;
            return $state;
        }
    ])
    ->run();

echo 'user 123 changed his name ' . $query->getState()['count'] . ' times';
```

You can also reset and run the query again:

```php
$query->reset();
$query->run();
```

Or you can stop the projection at any point in time.

```php
$query = $projectionManager->createQuery();
$query
    ->init(function (): array {
        return ['count' => 0];
    })
    ->fromStream('user-123')
    ->when([
        'user-name-changed' => function (
            array $state, UsernameChanged $event
        ): array {
            $state['count']++;
            $this->stop(); // stop query now
            return $state;
        }
    ])
    ->run();
```

Queries can be used to answer a given question easily, because you don't need to figure out in which read model the
data is present (maybe it's not?) and how to query it there (maybe a lot of joins are needed in RDBMS).
Also you can do temporal queries very easy, which is hard until impossible to do with any other database system.

## Projections

Projections are like queries, but first of all, they are persistent, the created state is persistent and can be queried
later, and also the projection is running forever (in most cases).

Compared to queries, the projectors have a couple of additional methods:

```php
public function getName(): string;

public function emit(Message $event): void;

public function linkTo(string $streamName, Message $event): void;

public function delete(bool $deleteEmittedEvents): void;
```

- getName() - obviously returns the given name of that projection
- emit(Message $event) - emits a new event that will be persisted on a stream with the same name as the projection
- linkTo(string $streamName, Message $event) - emits a new event, that will be persisted on a specific stream
- delete(bool $deleteEmittedEvents) - deletes the projection completely, the `$deleteEmittedEvents` flag tells, whether or not to delete emitted events.

An example:

```php
$projector = $projectionManager->createProjection('test_projection');
$projector
    ->fromStream('user-123')
    ->whenAny(
        function (array $state, Message $event): array {
            $this->linkTo('foo', $event); // create a copy of the event to a new stream
            return $state;
        }
    )
    ->run();
```

```php
$projector = $projectionManager->createProjection('test_projection');
$projector
    ->init(function (): array {
        return ['count' => 0];
    })
    ->fromCategory('user')
    ->when([
        'user-registered' => function (array $state, Message $event): array {
            $state['count']++;
            return $state;
        }
    )
    ->run();
```

This would count all registered users.

### Options

There are three options common to all projectors:

OPTION_CACHE_SIZE = 'cache_size';

The cache size is how many stream names are cached in memory, the higher the number to less queries are executed and therefor
makes the projections run faster, but it consumes more memory.

OPTION_SLEEP = 'sleep';

The sleep options tells the projection to sleep that many microseconds, before querying the event store again, when no events
were found in the last trip. This reduces having lots of cpu cycles without the projection doing anything really.

OPTION_PERSIST_BLOCK_SIZE = 'persist_block_size';

The persist block size tells the projector, to persist its changes after a given amount of operations. This increases the speed
of the projection a lot. When you persist only every 1000 events compared to persist on every event, then 999 write operations
are saved. The higher the number, the less write operations are made to your system, making the projections run faster.
On the other side, in case of an error, you need to redo the last operations again. If you are publishing events to the outside
world within a projection, you may think of a persist block size of 1 only.

OPTION_LOCK_TIMEOUT_MS = 'lock_timeout_ms'

Indicates the time (in microseconds) the projector is locked. During this time no other projector with the same name can
be started. A running projector will update the lock timeout on every loop.

OPTION_PCNTL_DISPATCH = 'false'

Enable dispatching of process signals to registered [signal handlers](http://php.net/manual/en/function.pcntl-signal.php) while
the projection is running. You must still register your own signal handler and take according action.
For example to gracefully stop the projection you could do
```
$projection = $projectionManager->createProjection(
    'test_projection',
    [ Projector::OPTION_PCNTL_DISPATCH => true, ]
);
pcntl_signal(SIGQUIT, function () use ($projection) {
    $projection->stop();
});
$projection->run();
```

## Read Model Projections

Projections can also be used to create read models. A read model has to implement `Prooph\EventStore\Projection\ReadModel`.
Prooph also ships with an `Prooph\EventStore\Projection\AbstractReadModel` that helps you to implement a read model yourself.

One nice thing about read model projections is, that you don't need a migration script for your read models at all.
When you need to make a change to your read model, you simply alter your read model implementation, stop your
current running projections, reset it and run it again.

### Options

The read model projectors have the same options as the normal projectors, see above for more explanations.

### Example

```php
$projector = $projectionManager->createReadModelProjection(
    'test_projection',
    $readModel
);

$projector
    ->fromAll()
    ->when([
        'user-created' => function ($state, Message $event) {
            $this->readModelProjection()->insert(
                'name',
                $event->payload()['name']
            );
        },
        'username-changed' => function ($state, Message $event) {
            $this->readModelProjection()->update(
                'name',
                $event->payload()['name']
            );
        }
    ])
    ->run();
```

## Projection Manager

The projection manager can do the following for you:

- Create queries & projectors
- Delete / reset / stop projections
- Fetch projection names
- Fetch projection status
- Fetch projection stream position
- Fetch projection state

While the most is pretty straightforward, the delete / reset / stop projection methods may need some additional
explanation:

When you call stopProjection($name) (or delete or reset) a message is send to the projection. This will notify the
running projection, that it should act accordingly. This means, that once you call `stopProjection` the projection
might not be stopped immediately, but it could take a few seconds until the projection is finally stopped.

## Internal projection mames

All internal projetion names are prefixed with `$` (dollar-sign), f.e. `$ct-`. Do not use projection names starting
with a dollar-sign, as this is reserved for prooph internals.
