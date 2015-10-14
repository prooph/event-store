ProophEventStore
===================
PHP 5.5+ EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/event-store?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

## Overview

Prooph Event Store is capable of persisting event objects that are organized in streams. The [EventStore](src/EventStore.php)
itself is a facade for different persistence adapters (see the list below) and adds event-driven hook points for [plugins](src/Plugin/Plugin.php)
which make the Event Store highly customizable.

## Installation

You can install prooph/event-store via composer by adding `"prooph/event-store": "~5.0"` as requirement to your composer.json.

## Available Persistence Adapters
- [Mongo DB](https://github.com/prooph/event-store-mongodb-adapter) - stable
- [Doctrine DBAL](https://github.com/prooph/event-store-doctrine-adapter) - stable

## Quick Start

For a short overview please see the annotated [quickstart.php](examples/quickstart.php) script.

## Documentation

- [Prooph Event Store](docs/event_store.md)
- [Working with Repositories](docs/repositories.md)

- [Upcasting](docs/upcasting.md)

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/event-store/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

# Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

# Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.

# License

Released under the [New BSD License](LICENSE).
