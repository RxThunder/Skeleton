# ![Thunder logo](https://github.com/RxThunder/Core/raw/master/resources/thunder-logo.svg?sanitize=true)

[![Licence](https://poser.pugx.org/rxthunder/core/license)](https://packagist.org/packages/RxThunder/core)
[![Latest Stable Version](https://poser.pugx.org/rxthunder/core/v/stable)](https://packagist.org/packages/RxThunder/core)

## About Thunder CLI μFramework

This repository is the scaffold of the Thunder CLI micro-framework.

You can use it and modify the structure to adapt on your needs.

## Philosophy

Sometimes you just need a very simple deamon, eventloop based, when you're event programming.
A small repository for your boundary, with few dependencies, and don't want to
be force to use a library or an other.

All you need is a reaction to an event that has been occurred in your system,
externally or internally (pub/sub or saga f.e.).

However, this project is born inside a repository using Reactive Programming
by default, RabbitMQ consumers, a router, EventStore projections and events,
mysql, Redis connectors, and many more.

Simple but powerful.

## What built in

### Console Component

The whole concept is base on consoles.
Instead of reinvent the wheel, Thunder use [Silly](https://github.com/mnapoli/silly)
micro-framework. An `AbstractConsole` is provided by the framework, allowing
classes extending it to be automatically loaded and usable.

### Dependency Injection

Usage of the dependency injection pattern, ease decoupling your code and quality
test writing. [Symfony DI](https://symfony.com/doc/current/components/dependency_injection.html)
component has been chosen for it many features [](https://symfony.com/doc/current/components/dependency_injection.html#learn-more)
improving developer experience.

### Configuration Component

This component allows you to use XML, YAML & PHP files. You can access and use
them directly into constructors through DI.

### Router

This component is central in the project, Command Bus and Event Dispatcher
pattern aren't adapted to non acknowledge (`nack`) messages.

It force to use [Subjects](http://reactivex.io/documentation/subject.html) in cases
where `ack/nack` is necessary on the message.

It needs some `AbstractRoute` and send an `AbstractSubject` to the right one.
An `AbstractRoute` can be associated to a `Controller` in traditional software
architecture pattern.

## Installation

`composer create-project rxthunder/skeleton name-of-your-project`

## Usage

### Console

At the begining, there is a console.
CLI for Command Line Interface.

To start the project you need to execute a PHP file available in the vendors, `vendor/bin/thunder`.

All commands available will be prompted.

### Subject

When your are consuming a source of data, each message is converted into an
`AbstractSubject`. This subject is an `Observable` & an `Observer` with some
subscribers already plugged.

One of the subscribers is responsible to handle the `acknowledge/non-acknowledge` behavior.

So basically, when the subject is completed, the message is acknowledged, and
you will received a new message when available.

### Create a new Route

Just extends the `Th3Mouk\Thunder\Route\AbstractRoute` into `/src` and it will
be automatically added to the router.

You can modify the `config/services.php` and `composer.json` autoload if you
don't want to use `src` folder.

#### Extra: Handler

I personally use `src/route` and `src/handler` structure.
The term `handler` coming from the Command Bus pattern.

An handler here, is a small invokable unit that can be reused in multiple
contexts, sagas of events, or here again routes.
The main advantage of this, is you can decouple your code and test it correctly,
because yes, this handler will be automatically injected in the container,
and you can use DI in its constructor :tada:.

An example can be:

```php
// src/Handler/PromptHello.php
namespace App\Handler;

use Rx\Observable;
use Th3Mouk\Thunder\Router\AbstractSubject;

class PromptHello
{
    public function __invoke(AbstractSubject $subject)
    {
        return Observable::of($subject)
            ->do(function() {
                echo "hello there". PHP_EOL;
            });
    }
}
```

And finally you can use this unit in your route:

```php
// src/Route/Test.php
namespace App\Route;

use App\Handler\PromptHello;
use Rx\Observable;
use Th3Mouk\Thunder\Router\AbstractRoute;
use Th3Mouk\Thunder\Router\AbstractSubject;

class Test extends AbstractRoute
{
    public const PATH = '/test';

    private $prompt;

    public function __construct(
        PromptHello $prompt
    ) {
        $this->prompt = $prompt;
    }

    public function __invoke(AbstractSubject $subject)
    {
        return Observable::of($subject)
            ->do(function () {
                echo "i'm in /test".PHP_EOL;
            })
            ->flatMap($this->prompt)
            ->do(function () {
                echo "passed in /test".PHP_EOL;
            })
            ->subscribe(function (AbstractSubject $subject) {
                $subject->onCompleted();
            });
    }
}
```

### Start consuming

If you use the RabbitMq consumer you can start with `php console listen:broker:rabbit test default`
`test` is the name of the queue to consume
`default` is the name of the connection to use

The connection system and configuration of RabbitMq is not documented yet and
probably will be extracted into a plug & play plugin.

Each message received by the queue will be transformed into a `RabbitMq/Subject`
and the **routing key** is the pattern the router is looking into the collection
of routes.

For example a message with `/test` routing key in Rabbit will be consumed by
the route with `public const PATH = '/test';`
