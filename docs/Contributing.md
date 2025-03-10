[Documentation](Index.md) / Contributing

# Websocket: Contributing

Everyone is welcome to help out!
But to keep this project sustainable, please ensure your contribution respects the requirements below.

## Pull Request requirements

Requirements on pull requests;
* All tests **MUST** pass.
* Code coverage **MUST** remain at 100%.
* Code **MUST** adhere to PSR-1 and PSR-12 code standards.

## SemVer, versions, and target branches

This repo uses strict [Semantic Versioning](https://semver.org).

* MAJOR version when introducing breaking changes
* MINOR version when adding features
* PATCH version when fixing bugs and equivalent changes

Base your patch on corresponding version branch, and target that version branch in your pull request.

| Version | Branch | PHP | Status |
| --- | --- | --- | --- |
| [`3.3`](https://github.com/sirn-se/websocket-php/tree/3.3.0) | `v3.3-main` | `^8.1` | Upcoming version |
| [`3.2`](https://github.com/sirn-se/websocket-php/tree/3.2.0) | `v3.2-main` | `^8.1` | Current version |
| [`3.1`](https://github.com/sirn-se/websocket-php/tree/3.1.0) | `v3.1-main` | `^8.1` | Bug fixes only |
| [`3.0`](https://github.com/sirn-se/websocket-php/tree/3.0.0) | - | `^8.1` | Not supported |
| [`2.2`](https://github.com/sirn-se/websocket-php/tree/2.2.0) | `v2.2-main` | `^8.0` | Bug fixes only |
| [`2.1`](https://github.com/sirn-se/websocket-php/tree/2.1.0) | - | `^8.0` | Not supported |
| [`2.0`](https://github.com/sirn-se/websocket-php/tree/2.0.0) | - | `^8.0` | Not supported |
| [`1.7`](https://github.com/sirn-se/websocket-php/tree/1.7.0) | - | `^7.4\|^8.0` | Not supported |
| [`1.6`](https://github.com/sirn-se/websocket-php/tree/1.6.0) | - | `^7.4\|^8.0` | Not supported |
| [`1.5`](https://github.com/sirn-se/websocket-php/tree/1.5.0) | - | `^7.4\|^8.0` | Not supported |
| [`1.4`](https://github.com/sirn-se/websocket-php/tree/1.4.0) | - | `^7.1` | Not supported |
| [`1.3`](https://github.com/sirn-se/websocket-php/tree/1.3.0) | - | `^5.4\|^7.0` | Not supported |
| [`1.2`](https://github.com/sirn-se/websocket-php/tree/1.2.0) | - | - | Not supported |
| [`1.1`](https://github.com/sirn-se/websocket-php/tree/1.1.0) | - | - | Not supported |
| [`1.0`](https://github.com/sirn-se/websocket-php/tree/1.0.0) | - | - | Not supported |

## Policy

* Library **MUST** provide core functionality, fully compatible with [WebSocket](https://datatracker.ietf.org/doc/html/rfc6455) standard.
* Library **MAY** provide optional functionality of general use. Such functionality should preferably be offered as middleware, or possibly as configuration option.
* Library must **NOT** make assumptions on how it will be used.
* Library must **NOT** provide specific service implementations. Such implementations should be provided in separate repo, using this library as a dependency.

## Dependency management

Install or update dependencies using [Composer](https://getcomposer.org/).

```
# Install dependencies
make install

# Update dependencies
make update
```

## Code standard

This project uses [PSR-1](https://www.php-fig.org/psr/psr-1/) and [PSR-12](https://www.php-fig.org/psr/psr-12/) code standards.
```
# Check code standard adherence
make cs
```

## Unit testing

Unit tests with [PHPUnit](https://phpunit.readthedocs.io/), coverage with [Coveralls](https://github.com/php-coveralls/php-coveralls)
```
# Run unit tests
make test

# Create coverage
make coverage
```

## Static analysis

This project uses [PHPStan](https://phpstan.org/) for static analysis.
```
# Run static analysis
make stan
```

## Contributors

* Sören Jensen (maintainer)
* Fredrik Liljegren (orginator)
* Armen Baghumian Sankbarani
* Ruslan Bekenev
* Joshua Thijssen
* Simon Lipp
* Quentin Bellus
* Patrick McCarren
* swmcdonnell
* Ignas Bernotas
* Mark Herhold
* Andreas Palm
* pmaasz
* Alexey Stavrov
* Michael Slezak
* Pierre Seznec
* rmeisler
* Nickolay V
* Shmyrev
* Christoph Kempen
* Marc Roberts
* Antonio Mora
* Simon Podlipsky
* etrinh
* zgrguric
* axklim
* Dejan Levec
* Pieter Oliver
* Sebastian Hagens
* Adrian Mihai