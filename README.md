Symfony Messenger Polyfill
==========================

Allows to use Symfony Messenger with Symfony 3.4 and 4.0.

[![PHP Version](https://img.shields.io/badge/php-%5E7.1-blue.svg)](https://img.shields.io/badge/php-%5E7.1-blue.svg)
[![Latest Stable Version](https://poser.pugx.org/lendable/symfony-messenger-polyfill/v/stable)](https://packagist.org/packages/lendable/symfony-messenger-polyfill)
[![Latest Unstable Version](https://poser.pugx.org/klendable/symfony-messenger-polyfill/v/unstable)](https://packagist.org/packages/lendable/symfony-messenger-polyfill)

[![Build Status](https://travis-ci.org/Lendable/symfony-messenger-polyfill.svg?branch=master)](https://travis-ci.org/Lendable/symfony-messenger-polyfill)

Documentation
-------------

* [Installation](#installation)
* [Configuration](#configuration)
* [How to use](#how-to-use)

## Installation

**1.**  Add dependency with composer

```bash
composer require lendable/symfony-messenger-polyfill
```

**2.** Register the bundle in your Kernel

```php
return [
    //...
    Lendable\Polyfill\Symfony\MessengerBundle\MessengerBundle::class => ['all' => true],
];
```

## Configuration

The only important thing is that root key is `lendable_polyfill_messenger`.

## How to use

Everything is explained in the [Symfony Documentation](https://symfony.com/doc/current/messenger.html).

If we take [this](https://symfony.com/doc/current/messenger.html#routing) configuration example, instead
of writing:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'My\Message\Message':  amqp # The name of the defined transport
```

You would write:

```yaml
# config/packages/messenger.yaml
lendable_polyfill_messenger:
    messenger:
        routing:
            'My\Message\Message':  amqp # The name of the defined transport
```
