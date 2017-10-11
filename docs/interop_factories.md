# Interop Factories

Instead of providing a module, a bundle, a bridge or similar framework integration prooph/event-store ships with `interop factories`.

## Factory-Driven Creation

The concept behind these factories (see `src/Container` folder) is simple but powerful. It allows us to provide you with bootstrapping logic for the event store and related components
without the need to rely on a specific framework. However, the factories have three requirements.

### Requirements

1. Your Inversion of Control container must implement the [PSR Container interface](https://github.com/php-fig/container).
2. [interop-config](https://github.com/sandrokeil/interop-config) must be installed
3. The application configuration should be registered with the service id `config` in the container.

*Note: Don't worry, if your environment doesn't provide these requirements, you can
always bootstrap the components by hand. Just look at the factories for inspiration in this case.*

### InMemoryEventStoreFactory

If the requirements are met, you just need to add a new section in your application config ...

```php
[
    'prooph' => [
        'event_store' => [
            'default' => [
                'wrap_action_event_emitter' => true,
                'metadata_enrichers' => [
                    // The factory will get the metadata enrichers and inject them in the MetadataEnricherPlugin.
                    // Note: you can obtain the same result by instantiating the plugin yourself
                    // and pass it to the 'plugin' section bellow.
                    'metadata_enricher_1',
                    'metadata_enricher_2',
                    // ...
                ],
                'plugins' => [
                    //And again the factory will use each service id to get the plugin from the container
                    //Plugin::attachToEventStore($eventStore) is then invoked by the factory so your plugins
                    // get attached automatically
                    //Awesome, isn't it?
                    'plugin_1_service_id',
                    'plugin_2_service_id',
                    //...
                ],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'inmemoryeventstore' => [
                \Prooph\EventStore\Container\InMemoryEventStoreFactory::class,
                'default',
            ],
        ],
    ],
    //... other application config here
]
```

$eventStore = $container->get('inmemoryeventstore');

#### ReadOnlyEventStoreWrapper

If you want to have a read only event store, just add `'read_only' => true` to your event store config.

### InMemoryProjectionManagerFactory

```php
[
    'prooph' => [
        'projection_manager' => [
            'default' => [
                'event_store' => 'inmemoryeventstore',
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'inmemoryeventstoreprojectionmanager' => [
                \Prooph\EventStore\Container\InMemoryProjectionManagerFactory::class,
                'default',
            ],
        ],
    ],
    //... other application config here
]
```

$projectionManager = $container->get('inmemoryeventstoreprojectionmanager');
