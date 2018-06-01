# Projections

New in v7 are queries and projections.

## Queries

Here, we are discussing event store queries, not queries on your read model. An event store query reads one or
multiple event streams, aggregates some state from it and makes it accessible. A query is a non-persistent function,
that will only be executed once, and return a result. That's it.

To get started, let's take a simple example where we want to query the
event-store for how often a given user has changed his username.

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

Or you can stop the query at any point in time.

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

Queries can be used to answer a given question easily because you don't need to figure out in which read model the
data is present (maybe it's not?) and how to query it there (maybe a lot of joins are needed in RDBMS).
Also you can do temporal queries very easily, which is hard to impossible to do with any other database system.

## Projections

Projections are like queries, but they are persistent, the created state is also persistent and can be queried
later, and the projection runs forever (in most cases).

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
- delete(bool $deleteEmittedEvents) - deletes the projection completely, the `$deleteEmittedEvents` flag tells whether or not to delete emitted events.

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
    ])
    ->run();
```

This would count all registered users.

### Options

There are six options common to all projectors:

OPTION_CACHE_SIZE = 'cache_size'; //Default: 1000

The cache size is how many stream names are cached in memory, the higher the number the less queries are executed and therefore
the projection runs faster, but it consumes more memory.

OPTION_SLEEP = 'sleep'; //Default: 100000

The sleep options tells the projection to sleep that many microseconds before querying the event store again when no events
were found in the last trip. This reduces the number of cpu cycles without the projection doing any real work.

OPTION_PERSIST_BLOCK_SIZE = 'persist_block_size'; //Default: 1000

The persist block size tells the projector to persist its changes after a given number of operations. This increases the speed
of the projection a lot. When you only persist every 1000 events compared to persist on every event, then 999 write operations
are saved. The higher the number, the fewer write operations are made to your system, making the projections run faster.
On the other side, in case of an error, you need to redo the last operations again. If you are publishing events to the outside
world within a projection, you may think of a persist block size of 1 only.

OPTION_LOCK_TIMEOUT_MS = 'lock_timeout_ms'; //Default: 1000

Indicates the time (in milliseconds) the projector is locked. During this time no other projector with the same name can
be started. A running projector will update the lock timeout on every loop, except you configure an update lock threshold.

OPTION_PCNTL_DISPATCH = 'trigger_pcntl_dispatch'; //Default: false

Enable dispatching of process signals to registered [signal handlers](http://php.net/manual/en/function.pcntl-signal.php) while
the projection is running. You must still register your own signal handler and act accordingly.
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

OPTION_UPDATE_LOCK_THRESHOLD = 'update_lock_threshold'; //Default: 0

If update lock threshold is set to a value greater than 0 the projection won't update lock timeout until number of milliseconds
have passed. Let's say your projection has a sleep interval of 100 ms and a lock timeout of 1000 ms.
By default the projector updates lock timeout after each run so basically every 100 ms the lock timeout is set to: `now() + 1000 ms`
This causes a lot of extra work for your database and in case the database is replicated this can cause a lot of network traffic, too.

This is how the projection works without a threshold set:

```
1. Process new events. Update lock timeout -> now() + 1000 ms. Sleep 100 ms
2. Process new events. Update lock timeout -> now() + 1000 ms. Sleep 100 ms
3. Process new events. Update lock timeout -> now() + 1000 ms. Sleep 100 ms
...
```

And this is the projection flow with an update lock threshold set to `700 ms`:

```
1. Process new events. Sleep 100 ms
2. Process new events. Sleep 100 ms
3. Process new events. Sleep 100 ms
...
7. Process new events. Update lock timeout -> now() + 1000 ms. Sleep 100 ms
8. Process new events. Sleep 100 ms
```
   

## Read Model Projections

Projections can also be used to create read models. A read model has to implement `Prooph\EventStore\Projection\ReadModel`.
Prooph also ships with an `Prooph\EventStore\Projection\AbstractReadModel` that helps you implement a read model yourself.

One nice thing about read model projections is that you don't need a migration script for your read models.
When you need to make a change to your read model, you simply alter your read model implementation, stop your
current running projections, reset it, and run it again.

### Options

The read model projectors have the same options as the normal projectors. See above for more explanations.

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

While most methods are pretty straightforward, the delete / reset / stop projection methods may need some additional
explanation:

When you call stopProjection($name) (or delete or reset) a message is sent to the projection. This will notify the
running projection that it should act accordingly. This means that once you call `stopProjection`, it could take a few
seconds before the projection is finally stopped.

## Internal projection names

All internal projection names are prefixed with `$` (dollar-sign), f.e. `$ct-`. Do not use projection names starting
with a dollar-sign, as this is reserved for prooph internals.
