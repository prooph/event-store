# Prooph Event Store

Prooph Event Store is the central component of this package. If you are familiar with doctrine
you can compare it with doctrine's EntityManager.
However, Prooph Event Store is especially designed to add a centralized, event-driven system on top
of different low level event stream persistence adapters (f.e. MySQL or Postgres).
The event-driven store is the unique selling point of prooph/event-store compared to other libraries.
So let's directly jump into it and see what you can do with it.

## Event Hooks

Requirements: an event store wrapped with `Prooph\EventStore\ActionEventEmitterEventStore`.

Action events are triggered when methods of the event store are invoked. The action events are named like the 
event store methods. The following events are available (event target is always the event store):

- `create`: event params: `stream` - result params: `streamExistsAlready`
- `appendTo`: event params: `streamName`, `streamEvents` - result params: `streamNotFound`, `concurrencyException`
- `load`: event params: `streamName`, `fromNumber`, `count`, `metadatamatcher` - result params: `streamEvents`, `streamNotFound`
- `loadReverse`: event params: `streamName`, `fromNumber`, `count`, `metadatamatcher` - result params: `streamEvents`, `streamNotFound`
- `delete`: event params: `streamName` - result params: `streamNotFound`
- `hasStream`: event params: `streamName` - result params: `result`
- `fetchStreamMetadata`: event params: `streamName` - result params: `metadata`, `streamNotFound`
- `updateStreamMetadata`: event params: `streamName`, `metadata` - result params: `streamNotFound`
- `fetchStreamNames`: event params: `filter`, `metadataMatcher`, `limit`, `offset` - result params: `streamNames`
- `fetchStreamNamesRegex`: event params: `filter`, `metadataMatcher`, `limit`, `offset` - result params: `streamNames` 
- `fetchCategoryNames`: event params: `filter`, `offset`, `limit` - result params: `categoryNames`
- `fetchCategoryNamesRegex`: event params: `filter`, `offset`, `limit` - result params: `categoryNames`

If the event store implements additionally \Prooph\EventStore\CanControlTransactionActionEventEmitterAwareEventStore,
the following additional events are available:

- `beginTransaction`: event params: none - result params: `transactionAlreadyStarted`
- `commit`: event params: none - result params: `transactionNotStarted`
- `rollback`: event params: none - result params: `transactionNotStarted 

## Attaching Plugins

If you had a look at the quick start you should already be familiar with one possibility to attach an event listener plugin.

```php
$eventStore->attach(
    'commit',
    function (\Prooph\Common\Event\ActionEvent $actionEvent) {
        //plugin logic here
    },
    1000 // priority
);
```

More complex plugins are typically provided as classes with own dependencies. A plugin can implement the `Prooph\EventStore\Plugin\Plugin` interface
and can then attach itself to the event store in the `Plugin::attachToEventStore($eventStore)` method.
Implementing the interface is especially useful when you use the event store factory.

## Plugin Use Cases

The event-driven system opens the door for customizations. Here are some ideas what you can do with it:

- Attach a domain event dispatcher on the `create` and `appendTo` event
- Filter events before they are stored
- Add event metadata like a `causation id` (id of the command which caused the event)
- Convert events into custom event objects before they are passed back to a repository
- Implement your own Unit of Work and synchronizes it with the `transaction`, `commit` and `rollback` events
- ...

## Metadata enricher

By default the component is shipped with a plugin to automatically add metadata for each events.
For instance you may want to add information about the command which caused the event or even
the user who triggered that command.

Here is an example of usage:

```php
<?php

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;

class IssuerMetadataEnricher implements MetadataEnricher
{
    // ...

    public function enrich(Message $event): Message
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

$plugin->attachToEventStore($eventStore);
```
