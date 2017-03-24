# Projections

New in v7 are queries and projectons.

## Queries

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

## Read Model Projections

Projections can also be used to create read models. A read model has to implement `Prooph\EventStore\Projection\ReadModel`.
Prooph also ships with an `Prooph\EventStore\Projection\AbstractReadModel` that helps you to implement a read model yourself.

One nice thing about read model projections is, that you don't need a migration script for your read models at all.
When you need to make a change to your read model, you simply alter your read model implementation, stop your
current running projections, reset it and run it again.

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
