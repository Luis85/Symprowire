# Welcome to Symprowire
a PHP MVC Framework for ProcessWire

## How does it work?

Symprowire offers a structured way to render your ProcessWire files and compose your data.
The framework brings the VC from the MVC pattern to ProcessWire which brings the M.
To do so, Symprowire implements the Symfony/HttpFoundation and handles the template rendering in a Request - Process - Response workflow.
This extends Symprowire driven templates with the following features

- Twig
- Dependency Injection
- EventDispatcher

Symprowire integrates fully with ProcessWire and does not alter the Core in any way.
To use Symprowire, even in your existing sites, you just have to copy the controller template file.

## Requirements

1. composer
2. fresh installed ProcessWire 3.x
3. PHP >=8.0.2

## Installation

Symprowire gets called via Template file by ProcessWire and will be processed by page->render().
So make sure to have ProcessWire installed.
To seperate everything we asume our App lives in /site.

`cd site`

1. `composer install --no-dev`
2. `composer dump-autoload --optimize --no-dev`
3. copy `vendor/symprowire/templates/controller.php` into your templates folder
4. change the Alternative Templatefile to 'controller' if you want to let Symprowire render it

Make sure to not install the Dev-Dependencies as this will result in duplicate class Exceptions.
You have to follow PSR-4 naming conventions and have to use site/src for your App.

## Recommenced Modules

- Tracy Debugger

## Error Code Levels

- 100 -> Request // Errors thrown during Request creation
- 200 -> SymproWireEngine // Errors from the Kernel
- 300 -> SymprowireController // Userland
- 400 -> SymprowireService // Error Code Level for Services
