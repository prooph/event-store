# Migration from v6 to v7

## Moved classes

The `Prooph\EventStore\Snapshot\*` classes are now moved to their own repository, [SnapshotStore](https://github.com/prooph/snapshot-store).

The `Prooph\EventStore\Aggregate\*` classes are now moved into the [event-sourcing](https://github.com/prooph/event-sourcing/) repository.

## Interfaces VS adapters

The event store now ships with a `Prooph\EventStore\ReadOnlyEventStore` and a `Prooph\EventStore\EventStore` interface.
No event store adapters exist anymore, instead there are different implementations of the event store.

## ActionEventEmitterEventStore

In order to use action events like in v6, you need to wrap your event store.

```php
$eventStore = new ActionEventEmitterEventStore($eventStore);
```

or

```php
$eventStore = new TransactionalActionEventEmitterEventStore($eventStore);
```

Also, there are no more `.pre` and `.post` commit hooks anymore, instead, this is handled with different priorities now.

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
you need to upgrade your database before you do this (same for other db vendors of course). The way events are
persisted has changed and you cannot simply update your source code to make this change. You need to write a migration
script, take the database offline, perform the migration and go back online.

Things to do to migrate:
- Read all events
- Update event metadata
- Persist the event back to a new stream created with v7

This would need to be done for all event streams.

As this is a very tough job, we don't provide any migration script currently. For some applications, a downtime is not
acceptable, in which case v7 might not be the right choice. You should use it when you can take the application offline
for a while and perform the DB migration, or you can wait until you start a new project to use it.

We will support v6 series with bugfixes for at least a year (until mid 2018).
