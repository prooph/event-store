# Interop Factories

Instead of providing a module, a bundle, a bridge or similar framework integration prooph/event-store ships with `interop factories`.

## Factory-Driven Creation

The concept behind these factories (see `src/Container` folder) is simple but powerful. It allows us to provide you with bootstrapping logic for the event store and related components
without the need to rely on a specific framework. However, the factories have three requirements.

### Requirements

1. Your Inversion of Control container must implement the [interop-container interface](https://github.com/container-interop/container-interop).
2. [interop-config](https://github.com/sandrokeil/interop-config) must be installed
3. The application configuration should be registered with the service id `config` in the container.

*Note: Don't worry, if your environment doesn't provide the requirements. You can
always bootstrap the components by hand. Just look at the factories for inspiration in this case.*

### Event Store Factory

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

... and register `Prooph\EventStore\Container\EventStoreFactory` in your IoC container. We recommend using the service id `Prooph\EventStore\EventStore (EventStore::class)` for the event store
because other factories like the stream strategy factories try to locate the event store
by using this service id.

*Note: The available event store adapters also ship with factories. Please refer to the adapter packages for details.*

### Snapshot Store Factory

Same for the snapshot store ...

```php
[
    'prooph' => [
        'snapshot_store' => [
            'adapter' => [
                'type' => 'adapter_service_id', //The factory will use this id to get the adapter from the container
                //The options key is reserved for adapter factories
                'options' => []
            ],
        ]
    ],
    //... other application config here
]
```

... and register the `Prooph\EventStore\Container\Snapshot\SnapshotStoreFactory` in your IoC container. We recommend using the service id `Prooph\EventStore\Snapshot\SnapshotStore (SnapshotStore::class)` for the snapshot store.

*Note: The available snapshot store adapters also ship with factories. Please refer to the adapter packages for details.*

### AbstractAggregateRepositoryFactory

To ease set up of repositories for your aggregate roots prooph/event-store also ships with a `Prooph\EventStore\Container\Aggregate\AbstractAggregateRepositoryFactory`.
It is an abstract class implementing the `container-interop RequiresContainerId` interface. The `containerId` method
itself is not implemented in the abstract class. You have to extend it and provide the container id because each
aggregate repository needs a slightly different configuration and therefor needs its own config key.

*Note: You can have a look at the `ProophTest\EventStore\Mock\RepositoryMockFactory`. It sounds more complex than it is.*

Let's say we have a repository factory for a User aggregate root. We use `user_repository` as container id and add this
configuration to our application configuration:

```php
[
    'prooph' => [
        'event_store' => [
            'user_repository' => [ //<-- here the container id is referenced
                'repository_class' => MyUserRepository::class, //<-- FQCN of the repository responsible for the aggregate root
                'aggregate_type' => MyUser::class, //<-- The aggregate root FQCN the repository is responsible for
                'aggregate_translator' => 'user_translator', //<-- The aggregate translator must be available as service in the container
            ]
        ]
    ]
]
```

If you want to speed up loading of aggregates with a snapshot store then you need to make
it available as service in the container and use the configuration to let the factory inject the snapshot store in the repository.

```php
[
    'prooph' => [
        'event_store' => [
            'user_repository' => [
                'repository_class' => MyUserRepository::class,
                'aggregate_type' => MyUser::class,
                'aggregate_translator' => 'user_translator',
                'snapshot_store' => 'awesome_snapshot_store' // <-- SnapshotStore service id
            ]
        ]
    ]
]
```

You can also configure a custom stream name (default is `event_stream`):

```php
[
    'prooph' => [
        'event_store' => [
            'user_repository' => [
                'repository_class' => MyUserRepository::class,
                'aggregate_type' => MyUser::class,
                'aggregate_translator' => 'user_translator',
                'snapshot_store' => 'awesome_snapshot_store', // <-- SnapshotStore service id
                'stream_name' => 'user_stream' // <-- Custom stream name
            ]
        ]
    ]
]
```

Last but not least you can enable the so called "One-Stream-Per-Aggregate-Mode":
```php
[
    'prooph' => [
        'event_store' => [
            'user_repository' => [
                'repository_class' => MyUserRepository::class,
                'aggregate_type' => MyUser::class,
                'aggregate_translator' => 'user_translator',
                'snapshot_store' => 'awesome_snapshot_store', // <-- SnapshotStore service id
                'one_stream_per_aggregate' => true // <-- Enable One-Stream-Per-Aggregate-Mode
            ]
        ]
    ]
]
```


