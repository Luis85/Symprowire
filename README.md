# Welcome to Symprowire
a PHP MVC Framework for ProcessWire

## How does it work?

Symprowire offers a structured way to render your ProcessWire files and compose your data.
Symprowire implements the Symfony/HttpFoundation and handles the template rendering in a Request - Process - Response workflow.
This extends Symprowire driven templates with the following features

- Twig
- Dependency Injection
- EventDispatcher

Symprowire integrates fully with ProcessWire and does not alter the Core in any way.
To use Symprowire, even in your existing sites, you just have to copy the controller template file.

### Symprowire Routing - Template matching

Symprowire will generate his routing based on the available ProcessWire Templates. 
The runtime scans every Template for the alternative Templatefile "controller". 
This will add a /$template->name route to the RouteCollection which will always resolve to a Controller::index action.
To add new Views to your Template you either provide a _sympro=$method GET Parameter to your URL or you use Route Annotations.

## Requirements

1. composer
2. fresh installed ProcessWire 3.x
3. PHP >=8.0.2

## Installation

Symprowire gets called via Template file by ProcessWire and will be processed by page->render().

1. Download ProcessWire 3.x from the [ProcessWire](https://processwire.com/) Homepage
2. Install ProcessWire with debug mode enabled and setup your Environment
3. Make sure that `$config->appendTemplateFile` and `$config->prependTemplateFile` are empty
4. Now open up the `/site` folder in the Terminal of your liking
5. Init your Project with `composer init` and follow the instructions
7. `composer require symprowire/symprowire`
8. `composer install`
10. copy `vendor/symprowire/symprowire/templates/controller.php` into your templates folder
11. change the Alternative Templatefile to 'controller' if you want to let Symprowire render it

>You have to follow PSR-4 naming conventions and have to use site/src for your App in composer.json

## Running Tests in Develop

`php bin/phpunit`

## Recommended Modules

- Tracy Debugger

## Error Code Levels

- 100 -> Request // Errors thrown during Request creation
- 200 -> SymproWireEngine // Errors from the Kernel
- 300 -> SymprowireController // Userland
- 400 -> SymprowireService // Error Code Level for Services
