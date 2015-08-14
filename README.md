ProophEventStore
===================
PHP 5.5+ EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://img.shields.io/coveralls/prooph/event-store.svg)](https://coveralls.io/r/prooph/event-store?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

# Overview

ProophEventStore is capable of persisting event objects that are organized in streams. The [EventStore](src/EventStore.php)
itself is a facade for different persistence adapters (check the list below) and adds event-driven hook points for [features](src/Feature/Feature.php).
Features can provide additional functionality like publishing persisted events on an event bus or validate events before they are stored.
ProophEventStore ships with different [strategies](src/Stream/StreamStrategy.php) to organize event streams and a basic [repository](src/Aggregate/AggregateRepository.php) implementation for event sourced aggregate roots.
Each aggregate repository can work with another stream strategy to offer you maximum flexibility.

# Installation

You can install ProophEventStore via composer by adding `"prooph/event-store": "~5.0"` as requirement to your composer.json.


# Available Persistence Adapters
- [Doctrine DBAL](https://github.com/prooph/event-store-doctrine-adapter)
- [Mongo DB](https://github.com/prooph/event-store-mongodb-adapter)
- [ZF2 Tablegateway](https://github.com/prooph/event-store-zf2-adapter)

# StreamStrategies

- [SingleStreamStrategy](src/Stream/SingleStreamStrategy.php): Stores the events of all aggregates in one single stream
- [AggregateStreamStrategy](src/Stream/AggregateStreamStrategy.php): Creates a stream for each aggregate instance
- [AggregateTypeStreamStrategy](src/Stream/AggregateTypeStreamStrategy.php): Stores the events of all aggregates of the same type (f.e. all Users) in one stream

** Note ** Check the usage example to see how you can set up a repository with a stream strategy.

# AggregateTranslator

ProophEventStore wants to get out of your way as much as it can. To achieve this goal it requires neither a specific aggregate implementation
nor a domain event implementation. Instead it uses translation adapters which are responsible for translating custom domain events to [Prooph\Common\Messaging\Message](https://github.com/prooph/common/blob/master/src/Messaging/Message.php) and
to reconstitute an aggregate from it's event history. You are asked to provide an [AggregateTranslator](src/Aggregate/AggregateTranslator.php) for your aggregates or you use
[ProophEventSourcing](https://github.com/prooph/event-sourcing) which has build in support for prooph/event-store.

** Note ** Check the usage example to see how you can set up a repository with an AggregateTranslator.

# Persisting Custom Domain Events

ProophEventStore requires you to only provide [Prooph\Common\Messaging\Message](https://github.com/prooph/common/blob/master/src/Messaging/Message.php)s.
So you are free to use your own domain event implementations. Together with a custom `AggregateTranslator` it is possible to
decouple your model from ProophEventStore completely.
However, when you want to persist your own domain events you have to set up your event store adapter of choice with a
[Prooph\Common\Messaging\MessageFactory](https://github.com/prooph/common/blob/master/src/Messaging/MessageFactory.php)
and a [Prooph\Common\Messaging\MessageConverter](https://github.com/prooph/common/blob/master/src/Messaging/MessageConverter.php)
which can handle your domain events.
You can use `Prooph\EventStore\Configuration\Configuration` to pass your custom implementations via adapter options to the adapter.

```php
//Use setter methods to inject message factory and message converter
$config = new \Prooph\EventStore\Configuration\Configuration([
    'adapter' => [
        'type' => \Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter::class,
        'options' => [
            //connection settings go here
        ]
    ]
]);

$config->setMessageFactory($messageFactoryImpl);
$config->setMessageConverter($messageConverterImpl);

$eventStore = new EventStore($config);

//Or you pass them to the config within the array
$config = new \Prooph\EventStore\Configuration\Configuration([
    'adapter' => [
        'type' => \Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter::class,
        'options' => [
            //connection settings go here
        ]
    ],
    'message_factory' => $messageFactoryImpl,
    'message_converter' => $messageConverterImpl
]);

$eventStore = new EventStore($config);
```

# Configuration
As shown above the ProophEventStore ships with a [Configuration](src/Configuration/Configuration.php) to help you set up the event store.
All aspects of the event store can be customized by setting custom implementations either via `Configuration::set*` or by providing them in
the config array passed to the constructor of the `Configuration`:

- [Adapter](src/Adapter/Adapter.php) (`adapter` config key or `setAdapter` method)
- FeatureManager a DiC implementing [ContainerInterface](https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php)(`feature_manager` config key or `setFeatureManager` method)
- [MessageFactory](https://github.com/prooph/common/blob/master/src/Messaging/MessageFactory.php) (`message_factory` config key or `setMessageFactory` method)
- [MessageConverter](https://github.com/prooph/common/blob/master/src/Messaging/MessageConverter.php) (`message_converter` config key or `setMessageConverter` method)
- [ActionEventEmitter](https://github.com/prooph/common/blob/master/src/Event/ActionEventEmitter.php) (`action_event_emitter` config key or `setActionEventEmitter` method)

The [Adapter](src/Adapter/Adapter.php) can also be provided as a config array containing the `type` pointing to the FQCN of the adapter
and an optional `options` array passed to the constructor of the adapter.

Note: The [Adapter](src/Adapter/Adapter.php) interface does not require a `__construct(array $options)` constructor to be present, because you
can inject a ready-to-use adapter in the event store, but the configuration assumes that when you provide `adapter` as an config array
then the `type` FQCN will accept an options array as the only argument in the constructor. All event store adapters provided by prooph have
such a constructor signature.

You MUST provide the adapter in one way or the other. All other aspects are optional.

# Features

The `FeatureManager` is only useful together with [Features](src/Feature/Feature.php).
Features typically attach themselves as listeners to one or more action events triggered by the event store.
Action events are triggered when methods of the event store are invoked. The action events are named like the event store methods and most of them have
a suffix to indicate whether they are triggered before or after the logic of the method is executed.
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

## Feature Location
You may have read somewhere that service location is evil. Bad news: we use service location to get features from the `FeatureManager`
because the event store and/or the configuration cannot and do not want to know all the dependencies your features may have.
So you have to set up your container of choice to provide features and then you only pass the service ids to the
event store configuration.

Here is a very basic example setting a feature as a service to a container
and using the service id to tell the config which feature to get from the container:

```php
$container->set('my_event_store_feature', $myFeature);

$eventStoreConfig = new Configuration([
    'feature_manager' => $container,
    'features' => [
        'my_event_store_feature',
    ]
]);
```


# Usage

Check the [example](https://github.com/prooph/event-sourcing/blob/master/examples/quickstart.php) of ProophEventSourcing to see the EventStore in action.

# ZF2 Integration

[prooph/proophessor](https://github.com/prooph/proophessor) seamlessly integrates ProophEventStore with a ZF2 application.


# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/event-store/issues).

# Contribute

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

# Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.
