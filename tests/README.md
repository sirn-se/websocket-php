# Testing

Unit tests with [PHPUnit](https://phpunit.readthedocs.io/).


## How to run

To run all test, run in console.

```
make test
```


## Continuous integration

GitHub Actions are run on PHP versions `8.2`, `8.3`, `8.4`, `8.5` and `8.6`.


## Test strategy

Uses the [phrity/net-mock](https://packagist.org/packages/phrity/net-mock) library to mock
stream operations, implementing `expect<method>` before methods that uses stream interactions
are called.

This set up negates the dependency on running actual servers,
and allow testing various errors that might occur.
