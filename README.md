<p align="center"><img src="https://github.com/Th3Mouk/Thunder/raw/master/resources/thunder-logo.svg?sanitize=true"></p>

<p align="center">
<a href="https://packagist.org/packages/th3mouk/thunder-framework"><img src="https://poser.pugx.org/th3mouk/thunder-framework/license" alt="License"></a>
</p>

## About Thunder CLI Framework

This repository is the scaffold of the Thunder micro CLI framework.

You can use it and modify the structure to adapt on your needs.

## Installation

Plug & Play
`composer create-project th3mouk/thunder-framework name-of-my-project && mkdir name-of-the-project`

## Usage

### Console

At the begining, there is a console.
CLI for Command Line Interface.

To start the project you need to execute a PHP file available in the vendors, `vendor/bin/thunder`.

All commands available will be prompted.

### Subject

When your are consuming a source of data, each message is converted into an
`AbstractSubject`. This subject is an `Observable` with some subscribers already plugged.
One of the subscribers is responsible to handle the `acknowledge/non-acknowledge` behavior.

So basically, when the subject is completed, the message is acknowledged, and 
you will received a new one when available. 

### Create a new Route

Just extends the `Th3Mouk\Thunder\Route\AbstractRoute` and it will be automatically
added to the router.

#### Handler concept

I personnaly use `src/route` and `src/handler` structure for my file.
The term handler coming from the command bus pattern.

An handler is a small invokable unit that can be reused in multiple contexts, sagas of events.
The main advantage of this is you can decouple your code and test it correctly, 
because yes, this handler will be automatically injected in the container, 
and you can use DI in the constructor :tada:.

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
