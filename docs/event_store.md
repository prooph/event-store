# Prooph Event Store

Prooph Event Store is the central component of this package. If you are familiar with doctrine
you can compare it with doctrine's EntityManager.
However, Prooph Event Store is especially designed to add a centralized, event-driven system on top
of different low level event stream persistence adapters.
The event-driven store is the unique selling point of prooph/event-store compared to other libraries.
So let's directly jump into it and see what you can do with it.

## Event Hooks

Action events are triggered when methods of the event store are invoked. The action events are named like the event store methods and most of them have
a suffix to indicate whether they are triggered before or after the logic of the method itself is executed.
The following events are available (event target is always the event store):

- `create.pre`: event params: `stream`
- `create.post`: event params: `stream`
- `appendTo.pre`: event params: `streamName`, `streamEvents`
- `appendTo.post`: event params: `streamName`, `streamEvents`
- `load.pre`: event params: `streamName`, `minVersion`
  - If a listener injects a `stream` as event param and stops the event, the `stream` is returned immediately (adapter is not invoked)
  - If `stream` is false a `StreamNotFound` is thrown
- `load.post`: event params: `streamName`, `minVersion`, `stream`
  - If a listener stops the event, a `StreamNotFound` is thrown
- `loadEventsByMetadataFrom.pre`: event params: `streamName`, `minVersion`, `metadata`
  - If a listener injects a `streamEvents` iterator as event param and stops the event, `streamEvents` is returned immediately (adapter is not invoked)
- `loadEventsByMetadataFrom.post`: event params: `streamName`, `minVersion`, `metadata`, `streamEvents`
  - If a listener stops the event an empty iterator is returned from the method instead of `streamEvents`
- `beginTransaction`: no event params available
- `commit.pre`: no event params available
  - If a listener stops the event, a transaction rollback is triggered
- `commit.post`: event params: `recordedEvents`
  - This is the perfect action event to attach a `DomainEventDispatcher` which iterates over `recordedEvents` and publish them
- `rollback`: no event params available

## Attaching Plugins

If you had a look at the quick start you should already be familiar with one possibility to attach an event listener plugin.

```php
$eventStore->getActionEventEmitter()->attachListener(
    'commit.post',
    function (\Prooph\Common\Event\ActionEvent $actionEvent) {
        //plugin logic here
    }
);
```

More complex plugins are typically provided as classes with own dependencies. A plugin can implement the `Prooph\EventStore\Plugin\Plugin` interface
and can then attach itself to the event store in the `Plugin::setUp($eventStore)` method.
Implementing the interface is especially useful when you use the event store factory.

## Plugin Use Cases

The event-driven system opens the door for customizations. Here are some ideas what you can do with it:

- Attach a domain event dispatcher on the `commit.post` event
- Filter events before they are stored
- Add event metadata like a `causation id` (id of the command which caused the event)
- Convert events into custom event objects before they are passed back to a repository
- Implement your own Unit of Work and synchronizes it with the `transaction`, `commit.pre/post` and `rollback` events
- ...

## Metadata enricher

By default the component is shipped with a plugin to automatically add metadata for each events.
For instance you may want to add information about the command which caused the event or even
the user who triggered that command.

Here is an example of usage:

```php
<?php

class IssuerMetadataEnricher implements MetadataEnricher
{
    // ...

    public function enrich(Message $event)
    {
        if ($this->currentUser) {
            $event = $event
                ->withAddedMetadata('issuer_type', 'user')
                ->withAddedMetadata('issuer_id', $this->currentUser->id());
        }

        return $event;
    }
}

$plugin = new MetadataEnricherPlugin(new MetadataEnricherAggregate([
  $issuerMetadataEnricher,
  $causationMetadataEnricher,
  $otherMetadataEnricher,
]));

$eventStore->setUp($plugin);
```
