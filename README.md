# MaplePHP Blunder

![Screen dump on how blunder looks like](https://wazabii.se/github-assets/maplephp-blunder.png "MaplePHP Blunder")

**Blunder is a well-designed error handling framework for PHP.** It provides a pretty, user-friendly interface that simplifies debugging with excellent memory management. Blunder offers various handlers, including HTML, JSON, XML, plain text, and silent modes, allowing flexible error presentation. Seamlessly integrating with tools like the PSR-7 and PSR-3 compliant MaplePHP Log library, Blunder is an excellent choice for managing errors in PHP applications, helping users easily identify and resolve issues.

## Installation
Installation with composer

```bash
composer require maplephp/blunder
```

## Pretty Example

Here is a simple example to load the pretty error interface:

```php
use MaplePHP\Blunder\Run;
use MaplePHP\Blunder\Handlers\HtmlHandler;

$run = new Run(new HtmlHandler());
$run->load();
```

## Handlers

All handlers utilize the namespace `MaplePHP\Blunder\Handlers\[TheHandlerName]`.

* **HtmlHandler**: A user-friendly and visually appealing handler.
* **TextHandler**: Outputs a minified HTML text.
* **PlainTextHandler**: Outputs minified plain text.
* **JsonHandler**: Outputs errors as JSON.
* **XmlHandler**: Outputs errors as XML.
* **SilentHandler**: Suppresses error output but can log errors to files. You can choose to output fatal errors if necessary.

## Advanced Usage

### Event Handling

You can use Blunder's **event** functionality to handle errors, such as logging them to a file. The example below shows how to display a pretty error page in development mode and log errors in production.

#### 1. Install MaplePHP Log
We use [MaplePHP Log](https://github.com/MaplePHP/Log) in the example, a PSR-3 compliant logging library.

```bash
composer require maplephp/log
```

#### 2. Create an Event
Here is a complete example with explanatory comments.

```php
// Add the namespaces
use MaplePHP\Blunder\Run;
use MaplePHP\Blunder\Handlers\HtmlHandler;
use MaplePHP\Blunder\Handlers\SilentHandler;
use MaplePHP\Log\Logger;
use MaplePHP\Log\Handlers\StreamHandler;

// Bool to switch between dev and prod mode
$production = true;

// Initialize Blunder
$handler = ($production ? new SilentHandler() : new HtmlHandler());
$run = new Run($handler);

// Create the event
$run->event(function($item, $http) use($production) {

    if ($production) {
        // Initialize the MaplePHP PSR-3 compliant log library
        $log = new Logger(new StreamHandler("/path/to/errorLogFile.log", StreamHandler::MAX_SIZE, StreamHandler::MAX_COUNT));
        
        // The code below uses "getStatus" to call PSR-3 log methods like $log->error() or $log->warning().  
        call_user_func_array([$log, $item->getStatus()], [
            $item->getMessage(),
            [
                'flag' => $item->getSeverity(),
                'file' => $item->getFile(),
                'line' => $item->getLine()
            ]
        ]);
    }
    
});

// Load the error handling, and done
$run->load();
```

### HTTP Messaging

The Blunder `Run` class can take two arguments. The first argument is required and should be a class handler (`HandlerInterface`). The second argument is optional and expects an HTTP message class used to pass an already open PSR-7 response and `ServerRequest` instance instead of creating a new one for better performance.

```php
// $run = new Run(HandlerInterface, HttpMessagingInterface(ResponseInterface, ServerRequestInterface));
$run = new Run(new HtmlHandler(), new HttpMessaging($response, $request));
```