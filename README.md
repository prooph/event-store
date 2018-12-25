# Prooph Event Store

Common classes and interface for Prooph Event Store implementations.

[![Build Status](https://travis-ci.org/prooph/event-store.svg?branch=master)](https://travis-ci.org/prooph/event-store)
[![Coverage Status](https://coveralls.io/repos/github/prooph/event-store/badge.svg?branch=master)](https://coveralls.io/github/prooph/event-store?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

## Installation

You can install prooph/event-store via composer by adding `"prooph/event-store": "dev-master"` as requirement to your composer.json.

### Available persistent implementations

- [Event Store Client](https://github.com/prooph/event-store-client) for async TCP connections
- [Event Store HTTP Client](https://github.com/prooph/event-store-http-client) for HTTP connections

## Documentation

See: [https://github.com/prooph/documentation](https://github.com/prooph/documentation)

Will be published on the website soon.

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/event-store/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## Version Guidance

| Version | Status      | PHP Version | Support Until |
|---------|-------------|-------------|---------------|
| 5.x     | EOL         | >= 5.5      | EOL           |
| 6.x     | Maintained  | >= 5.5      | 3 Dec 2017    |
| 7.x     | Latest      | >= 7.1      | active        |
| 8.x     | Development | >= 7.2      | active        |

## License

Released under the [New BSD License](LICENSE).
