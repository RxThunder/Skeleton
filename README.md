<p align="center"><img src="https://github.com/RxThunder/Core/raw/master/resources/thunder-logo.svg?sanitize=true"></p>

<p align="center">
<a href="https://packagist.org/packages/RxThunder/core"><img src="https://poser.pugx.org/rxthunder/core/license" alt="License"></a>
<a href="https://packagist.org/packages/RxThunder/core"><img src="https://poser.pugx.org/rxthunder/core/v/stable" alt="License"></a>
</p>

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
The whole concept is based on consoles.
Instead of reinvent the wheel, Thunder use [Silly](https://github.com/mnapoli/silly)
micro-framework. A `Console` class being provided by the framework, allowing
classes that extend it to be automatically loaded and usable.

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

It's pretty straight forward, a `Route` can be associated to a `Controller` in a
MVC schema. The core provide a base class called `Route` that allow to
automatically load all classes extending it in the `Router`.

Each route have to define it's constant called `PATH`. It's used to determine
which `Route` of your application must be invoked.

As we are in an event driven framework, all `Route` must return an `Observable`.
The different plugins that you will use in your application, are subscribing to
your `Observable` using `Observers` to determine actions to execute at the end
of your event chain.

## Installation

`composer create-project rxthunder/skeleton name-of-your-project`

## Usage
### Console
At the begining, there is a console.
CLI for Command Line Interface.

To start the project you need to execute a PHP file available in the vendors, `vendor/bin/thunder`.

All commands available will be prompted.

### Create a new Route
Create a class extending `Th3Mouk\Thunder\Router\Route` into `/src` and it will
be automatically added to the router.

You can modify the `config/services.php` and `composer.json` autoload if you
don't want to use `src` folder.

Your `Route` will now be invoked with the corresponding `DataModel` through
the PATH constant.

#### Extra: Handler concept
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
use RxThunder\Core\Model\DataModel;

class PromptHello
{
    public function __invoke(DataModel $data_model)
    {
        return Observable::of($data_model)
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
use RxThunder\Core\Model\DataModel;
use RxThunder\Core\Router\Route;

class Test extends Route
{
    public const PATH = '/test';

    private $prompt;

    public function __construct(
        PromptHello $prompt
    ) {
        $this->prompt = $prompt;
    }

    public function __invoke(DataModel $data_model): Observable
    {
        return Observable::of($data_model)
            ->do(function () {
                echo "i'm in /test".PHP_EOL;
            })
            ->flatMap($this->prompt)
            ->do(function () {
                echo "passed in /test".PHP_EOL;
            });
    }
}
```

### Start consuming
If you use the RabbitMQ plugin you can start consuming a queue with
`php console rabbit:listen:broker test`
(`test` is the name of the queue to consume)

Each message received by the queue will be transformed into a `DataModel`
and the **routing key** correspond to the `type()` of it.

For example a message with `/test` routing key in RabbitMQ will be consumed by
the route with `public const PATH = '/test';`
