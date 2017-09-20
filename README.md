# Prooph Event Store

PHP 7.1 EventStore Implementation.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://coveralls.io/repos/prooph/event-store/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/event-store?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

## Overview

Prooph Event Store is capable of persisting event messages that are organized in streams. `Prooph\EventStore\EventStore`
itself is a facade for different persistence adapters (see the list below) and adds event-driven hook points for `Prooph\EventStore\Plugin\Plugin`s
which make the Event Store highly customizable.

## Installation

You can install prooph/event-store via composer by adding `"prooph/event-store": "^7.0"` as requirement to your composer.json.

## Available persistent implementations
- [PDO](https://github.com/prooph/pdo-event-store) - stable

## Available snapshot store implementations
- [Mongo DB](https://github.com/prooph/mongodb-snapshot-store) - stable
- [PDO](https://github.com/prooph/pdo-snapshot-store) - stable
- [Memcached](https://github.com/prooph/memcached-snapshot-store) - stable
- [ArangoDB](https://github.com/prooph/arangodb-snapshot-store) - under development

## Quick Start

For a short overview please see the annotated Quickstart in the `examples` folder.

## Documentation

Documentation is [in the doc tree](docs/), and can be compiled using [bookdown](http://bookdown.io).

```console
$ php ./vendor/bin/bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/event-store/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## Version Guidance

| Version | Status     | PHP Version | Support Until |
|---------|------------|-------------|---------------|
| 5.x     | EOL        | >= 5.5      | EOL           |
| 6.x     | Maintained | >= 5.5      | 3 Dec 2017    |
| 7.x     | Latest     | >= 7.1      | active        |

## License

Released under the [New BSD License](LICENSE).
