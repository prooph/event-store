ProophEventStore
===================
PHP 5.4+ EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store/badge.png)](https://coveralls.io/r/prooph/event-store)


##Goal of the library
ProophEventStore is the favorite persistence layer for most of our projects. We love the idea behind [Event Sourcing](http://martinfowler.com/eaaDev/EventSourcing.html)
not only within a DDD application but also in medium-sized projects. We've decided to develop a PHP EventStore implementation on basis
of Zend Framework 2 components. We need a flexible system but we did not want to reinvent the wheel for components like an inversion of control Container, DBAL or event-driven logic. The result is a very flexible event store library that can handle custom domain events and work together with a storage adapter and an event dispatcher of your choice.

##Features
- Event-driven library [in progress]
- Full DDD support (no dependency of ES in domain model) [done]
- Default event-sourcing components [done] -> but live in a separate library: [prooph/event-sourcing](https://github.com/prooph/event-sourcing)
- IdentityMap to support collection-oriented repositories [done]
- Transaction handling [done]
- Optional snapshot strategies [not started]
- Optional concurrent logging [not started]
- Link your own event dispatcher and/or connect [ProophServiceBus](https://github.com/prooph/service-bus) [done]
- FeatureManager to ease customization [done]
- Support for [event-centric/aggregates](https://github.com/event-centric/aggregates) [not started]
- Changeable storage adapter like
    - [ZF2 Tablegateway](https://github.com/prooph/event-store-zf2-adapter) [done]
    - [doctrine dbal](https://github.com/prooph/event-store-doctrine-adapter) [done]
    - mongoDB [not started]
    - geteventstore client [not started]

##Used Third-Party Libraries
- The ProophEventStore is based on [ZF2 components](http://framework.zend.com/) which offer a lot of customization options.
- Uuids of the StreamEvents are generated with [rhumsaa/uuid](https://github.com/ramsey/uuid)
- Assertions are performed by [beberlei/assert](https://github.com/beberlei/assert)

##Installation
You can install ProophEventStore via composer by adding `"prooph/event-store": "~0.5"` as requirement to your composer.json.


##Quick start

Check out the [example](https://github.com/prooph/event-sourcing/blob/master/examples/quickstart.php) of ProophEventSourcing to see the EventStore in action

What's next?
------------

Basic functionality of an EventStore is implemented but we want to add more features (like support for snapshots) until we will release the first stable version. We apologize for the lack of documentation. We are working on it.

ZF2 Integration
---------------

[ProophEventStoreModule](https://github.com/prooph/ProophEventStoreModule) seamlessly integrates ProophEventStore with a ZF2 application.

Acknowledgements
----------------
The library is heavily inspired by [event-centric/EventCentric.Core](https://github.com/event-centric/EventCentric.Core), [malocher/event-store](https://github.com/malocher/event-store), [beberlei/litecqrs-php](https://github.com/beberlei/litecqrs-php) and [szjani/predaddy](https://github.com/szjani/predaddy)




