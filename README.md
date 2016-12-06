# Hgraca\MicroDI
[![Author](http://img.shields.io/badge/author-@hgraca-blue.svg?style=flat-square)](https://www.herbertograca.com)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Latest Version](https://img.shields.io/github/release/hgraca/php-micro-di.svg?style=flat-square)](https://github.com/hgraca/php-micro-di/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/hgraca/micro-di.svg?style=flat-square)](https://packagist.org/packages/hgraca/micro-di)

[![Build Status](https://img.shields.io/scrutinizer/build/g/hgraca/php-micro-di.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-di/build)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/hgraca/php-micro-di.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-di/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/hgraca/php-micro-di.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-di)

A small dependency injection library that uses reflection and a service locator container, to instantiate and recursively resolve, instantiate and inject dependencies.

## Installation

To install the library, run the command below and you will get the latest version:

```
composer require hgraca/micro-di
```

## Usage

### Factories

The factories used in `Builder::buildFromFactory` need to extend `Hgraca\MicroDI\FactoryInterface`.

The method `Builder::buildFromFactory` instantiates the factory and calls the `create` method on it.
The arguments given to `Builder::buildFromFactory` will be used both when instantiating the factory and when
calling the `create` method:
* They will be injected in the `__construct()` if the arguments keys match any of the dependencies names.
* They will all be injected in the `create()`, together with whatever is in the container with the key
'<factoryFQNC>.context', where <factoryFQNC> is the relevant factory FQCN.

### Dependency resolution process

The builder will resolve and instantiate dependencies. The dependencies, both when instantiating and when calling
a method with  `Builder::call`, will fe filled in the following priority:

1. By matching the dependencies names with the name of the provided arguments
2. If its a class/interface, it will try to instantiate it, which in turn:
    1. tries to fetch it from the container, by searching for the class/interface fqcn as one of the containers keys
        1. If the key (class/interface fqcn) is found:
            * If its an instance, it will be used (**singleton**)
            * If its a closure, it will be used to instantiate the required class (**singleton**)
            * If its a factory, it will be used to build the required class through the method
            `Builder::buildFromFactory`, and build a new instance every time (making it **not a singleton**)
        2. If the key (class/interface fqcn) is not found:
            1. If its a class it will be instantiated (recursively resolving and injecting its dependencies) and it
            will be cached in the container
            2. If its an interface, an error will be thrown
3. If its not a class/interface it will try to find the dependencies in the container, by their name.
4. If it fails to find all mandatory dependencies, it will throw an error while instantiating the class.

## Todo

- tests
- Add `shields.io` badges
- Add a CS fixer
