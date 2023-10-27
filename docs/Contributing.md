[Documentation](Index.md) > Contributing

# Websocket: Contributing

Everyone is welcome to help out!
But to keep this project sustainable, please ensure your contribution respects the requirements below.

## PR Requirements

Requirements on pull requests;
* All tests **MUST** pass.
* Code coverage **MUST** remain at 100%.
* Code **MUST** adhere to PSR-1 and PSR-12 code standards.

Base your patch on corresponding version branch, and target that version branch in your pull request.

| Version | Branch | PHP | Status |
| --- | --- | --- | --- |
| [`2.0`](https://github.com/sirn-se/websocket-php/tree/2.0.0) | `v2.0-main` | `^8.0` | Current version |
| [`1.7`](https://github.com/sirn-se/websocket-php/tree/1.7.0) | `v1.7-master` | `^7.4\\^8.0` | Bug fixes only |
| [`1.6`](https://github.com/sirn-se/websocket-php/tree/1.6.0) | `v1.6-master` | `^7.4\\^8.0` | - |
| [`1.5`](https://github.com/sirn-se/websocket-php/tree/1.5.0) | `v1.5-master` | `^7.4\\^8.0` | - |
| [`1.4`](https://github.com/sirn-se/websocket-php/tree/1.4.0) | - | `^7.1` | - |
| [`1.3`](https://github.com/sirn-se/websocket-php/tree/1.3.0) | - | `^5.4\\^7.0` | - |
| [`1.2`](https://github.com/sirn-se/websocket-php/tree/1.2.0) | - | - | - |
| [`1.1`](https://github.com/sirn-se/websocket-php/tree/1.1.0) | - | - | - |
| [`1.0`](https://github.com/sirn-se/websocket-php/tree/1.0.0) | - | - | - |


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
make cs-check
```

## Unit testing

Unit tests with [PHPUnit](https://phpunit.readthedocs.io/), coverage with [Coveralls](https://github.com/php-coveralls/php-coveralls)
```
# Run unit tests
make test

# Create coverage
make coverage
```

## Contributors

* Sören Jensen (maintainer)
* Fredrik Liljegren
* Armen Baghumian Sankbarani
* Ruslan Bekenev
* Joshua Thijssen
* Simon Lipp
* Quentin Bellus
* Patrick McCarren
* swmcdonnell,
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
* Christoph Kempen,
* Marc Roberts
* Antonio Mora
* Simon Podlipsky
* etrinh
