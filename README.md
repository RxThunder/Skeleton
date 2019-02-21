<p align="center"><img src="https://github.com/Th3Mouk/Thunder/raw/master/resources/thunder-logo.svg?sanitize=true"></p>

<p align="center">
<a href="https://packagist.org/packages/th3mouk/thunder-framework"><img src="https://poser.pugx.org/th3mouk/thunder-framework/license" alt="License"></a>
</p>

## About Thunder CLI Framework
This repository is the scaffold of the Thunder micro CLI framework.

You can use it and modify the structure to adapt on your needs.

## Philosophy
Sometimes you just need something very simple when you're event programming, 
a small repository for your boundary, with few dependencies, and don't want to 
be force to use a library or an other.

All you need is a reaction to an event that has been occurred in your system, 
externally or internally.

However, this project is born inside a repository using Reactive Programming 
by default, RabbitMQ consumers, a router, EventStore projections and events, 
mysql, Redis connectors, and many more.

Simple but powerful.

## Why Built in
### Console
No mystery, CLI = Command line interface

### Dependency Injection
Usage of the dependency injection pattern, ease decoupling your code and quality 
test writing. I recommend it as a best practice.  

### Configuration
This component allows you to use XML, YAML & PHP files, and you can access and use 
them directly into constructors through DI.

### Router
This component is the base of the project, Command Bus and Event Dispatcher 
pattern aren't adapted to non acknowledge (`nack`) messages.

It force to use [Subjects](http://reactivex.io/documentation/subject.html) in cases 
where `ack/nack` is necessary on the message.

It needs some `AbstractRoute` and send an `AbstractSubject` to the right one.
An `AbstractRoute` can be associated to a `Controller` in traditional software 
architecture pattern.

## Installation

`composer create-project rxthunder/skeleton name-of-my-project && mkdir name-of-the-project && touch .env`

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
