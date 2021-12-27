# Welcome to Symprowire
an PHP MVC Framework for ProcessWire

## Requirements

1. composer
2. fresh installed ProcessWire 3

## Dependencies

- symfony/runtime
- symfony/http-kernel
- symfony/http-foundation
- twig/twig
- processwire/processwire ^3.19

## Installation

1. `composer install --no-dev`
2. `composer dump-autoload --optimize --no-dev`
3. copy `controller.php` into your templates folder
4. change the Alternative Templatefile of the home Template to 'controller'

## Recommended Modules

- Tracy Debugger

## Error Code Levels

- 100 -> Request
- 200 -> SymproWireEngine
- 300 -> SymprowireController
- 400 -> SymprowireService
