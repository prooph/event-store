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
  - If `stream` is false a `StreamNotFoundException` is thrown
- `load.post`: event params: `streamName`, `minVersion`, `stream`
  - If a listener stops the event, a `StreamNotFoundException` is thrown
- `loadEventsByMetadataFrom.pre`: event params: `streamName`, `minVersion`, `metadata`
  - If a listener injects a `streamEvents` array as event param and stops the event, `streamEvents` is returned immediately (adapter is not invoked)
- `loadEventsByMetadataFrom.post`: event params: `streamName`, `minVersion`, `metadata`, `streamEvents`
  - If a listener stops the event an empty array is returned from the method instead of `streamEvents`
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

More complex plugins are typically provided as classes with own dependencies. A plugin can implement the [Plugin](src/Plugin/Plugin.php) interface
and can then attach itself to the event store in the `Plugin::setUp($eventStore)` method.
Implementing the interface is especially useful when you use the event store factory described in the [Container-Driven Creation](#container-driven-creation) section.

## Plugin Use Cases

The event-driven system opens the door for customizations. Here are some ideas what you can do with it:

- Attach a domain event dispatcher on the `commit.post` event
- Filter events before they are stored
- Add event metadata like a `causation id` (id of the command which caused the event)
- Convert events into custom event objects before they are passed back to a repository
- Implement your own Unit of Work and synchronizes it with the `transaction`, `commit.pre/post` and `rollback` events
- ...

## Container-Driven Creation

If you are familiar with the factory pattern and you use an implementation of [interop-container](https://github.com/container-interop/container-interop)
in your project you may want to have a look at the factories shipped with prooph/event-store.
You can find them in the [Container](src/Container) folder.

### Requirements

1. Your Inversion of Control container must implement the [interop-container interface](https://github.com/container-interop/container-interop).
2. The application configuration should be registered with the service id `config` in the container.

*Note: Don't worry, if your environment doesn't provide the requirements. You can
always bootstrap the event store by hand. Just look at the factories for inspiration in this case.*

If the requirements are met you just need to add a new section in your application config ...

```php
[
    'prooph' => [
        'event_store' => [
            'adapter' => [
                'type' => 'adapter_service_id', //The factory will use this id to get the adapter from the container
                //The options key is reserved for adapter factories
                'options' => []
            ],
            'event_emitter' => 'emitter_service_id' //The factory will use this id to get the event emitter from the container
            'plugins' => [
                //And again the factory will use each service id to get the plugin from the container
                //Plugin::setUp($eventStore) is then invoked by the factory so your plugins get attached automatically
                //Awesome, isn't it?
                'plugin_1_service_id',
                'plugin_2_service_id',
                //...
            ]
        ]
    ],
    //... other application config here
]
```

... and register the factory in your IoC container. We recommend using the service id `prooph.event_store` for the event store
because other factories like the [stream factories](src/Container/Stream) try to locate the event store
by using this service id.

*Note: The available event store adapters also ship with factories. Please refer to the adapter packages for details.*