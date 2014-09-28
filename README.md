ProophEventStore
===================
PHP 5.4+ EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://img.shields.io/coveralls/prooph/event-store.svg)](https://coveralls.io/r/prooph/event-store?branch=master)

# About Prooph

Prooph is the organisation behind [gingerframework](https://github.com/gingerframework/gingerframework) - a workflow framework written in PHP.
The founder and lead developer is [codeliner](https://github.com/codeliner). Prooph provides CQRS+ES infrastructure components for the gingerframework.
The components are split into 3 major libraries [ProophServiceBus](https://github.com/prooph/service-bus), [ProophEventSourcing](https://github.com/prooph/event-sourcing),
[ProophEventStore](https://github.com/prooph/event-store) and various minor libraries which add additional features or provide support for other frameworks.
The public APIs of the major components are stable. They are loosely coupled among each other and with the gingerframework, so you can mix and match them with
other libraries.

# Installation

You can install ProophEventStore via composer by adding `"prooph/event-store": "~0.5"` as requirement to your composer.json.


#Features

ProophEventStore is capable of persisting event objects that are organized in streams. The [EventStore](../src/Prooph/EventStore/EventStore.php)
itself is a facade for different persistence adapters (check the list below) and adds event-driven hook points for features.
Features can provide additional functionality like publishing persisted events on an event bus or validate events before they are stored.
ProophEventStore ships with three different strategies to organize event streams and a base repository implementation for event sourced aggregate roots.
Each aggregate repository can work with another stream strategy to offer you maximum flexibility.


# Available Persistence Adapters
- [ZF2 Tablegateway](https://github.com/prooph/event-store-zf2-adapter)
- [doctrine dbal](https://github.com/prooph/event-store-doctrine-adapter)

# StreamStrategies

- [SingleStreamStrategy](../src/Prooph/EventStore/Stream/SingleStreamStrategy.php): Stores the events of all aggregates in one single stream
- [AggregateStreamStrategy](../src/Prooph/EventStore/Stream/AggregateStreamStrategy.php): Creates a stream for each aggregate instance
- [AggregateTypeStreamStrategy](../src/Prooph/EventStore/Stream/AggregateTypeStreamStrategy.php): Stores the events of all aggregates of the same type (f.e. all Users) in one stream

** Note ** Check the usage example to see how you can set up a repository with a stream strategy.

# AggregateTranslator

ProophEventStore wants to get out of your way as much as it can. To achieve this goal it requires neither a specific aggregate implementation
nor a domain event implementation. Instead it uses translation adapters which are responsible for translating recorded domain events to [StreamEvents](../src/Prooph/EventStore/Stream/StreamEvent.php) and
to reconstitute an aggregate from it's event history. You are asked to provide an [AggregateTranslator](../src/Prooph/EventStore/Aggregate/AggregateTranslatorInterface) for your aggregates or you use
[ProophEventSourcing](https://github.com/prooph/event-sourcing) that has a ready-to-use [AggregateTranslator](https://github.com/prooph/event-sourcing/blob/master/src/Prooph/EventSourcing/EventStoreIntegration/AggregateTranslator.php) on board.

** Note ** Check the usage example to see how you can set up a repository with an AggregateTranslator.

# Usage

Check the [example](https://github.com/prooph/event-sourcing/blob/master/examples/quickstart.php) of ProophEventSourcing to see the EventStore in action.

# ZF2 Integration

[ProophEventStoreModule](https://github.com/prooph/ProophEventStoreModule) seamlessly integrates ProophEventStore with a ZF2 application.


# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/service-bus/issues).

# Contribute

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

# Used Third-Party Libraries

- The ProophEventStore is based on [ZF2 components](http://framework.zend.com/) which offer a lot of customization options.
- Uuids of the StreamEvents are generated with [rhumsaa/uuid](https://github.com/ramsey/uuid)
- Assertions are performed by [beberlei/assert](https://github.com/beberlei/assert)

# Acknowledgements
The library is heavily inspired by [event-centric/EventCentric.Core](https://github.com/event-centric/EventCentric.Core), [malocher/event-store](https://github.com/malocher/event-store), [beberlei/litecqrs-php](https://github.com/beberlei/litecqrs-php) and [szjani/predaddy](https://github.com/szjani/predaddy)




