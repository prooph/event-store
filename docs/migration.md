# Migration from v6 to v7

## Moved classes

The `Prooph\Snapshot\*` classes are now moved to its own repository [SnapshotStore](https://github.com/prooph/snapshot-store).

The `Prooph\Aggregate\*` classes are now moved into the [event-sourcing](https://github.com/prooph/event-sourcing/) repository.

## Interfaces VS adapters

The event store now ships with a `Prooph\EventStore\ReadOnlyEventStore` and a `Prooph\EventStore\EventStore` interface.
No event store adapters exist any more, instead there are different implementations of an event store.

## ActionEventEmitterEventStore

In order to use action events like in v6, you need to wrap your event store.

```php
$eventStore = new ActionEventEmitterEventStore($eventStore);
```

or

```php
$eventStore = new TransactionalActionEventEmitterEventStore($eventStore);
```

Also there are no more `.pre` and `.post` commit hooks any more, instead this is handled with different priorities now.

## Plugins

Instead of calling

```php
$plugin->setUp($eventStore);
```

you now need to call

```php
$plugin->attachToEventStore($eventStore);
```

## Interaction with the event store

If you are using the event-store together with the [event-sourcing](https://github.com/prooph/event-sourcing/) component,
most stuff is pretty much unchanged for you, as you don't interact with the event store directly (this is done by the
event-sourcing component).

If you are making calls to the event store yourself, take a look at the event_store docs on how the new usage is.

## DB migrations

If you are using v6 with MySQL (using doctrine adpater) and you want to switch to v7 with MySQL (using pdo-event-store),
you need to upgrade your database before you do this. The way events are persisted have changed and you cannot simply
update your source code to make this change. You need to write a migration script, take the database offline,
perform the migration and go back online.

As this is a very tough job, we don't provide any migration script currently and for some applications a downtime is not
acceptable, then v7 might not be the right choice for you, use it when you can take the application offline for a while
and you can perform the db migration or wait with v7 usage, until you start a new project.

We will support v6 series with bugfixes at least for a year (until mid 2018).
