# Symprowire - PHP MVC Framework for ProcessWire 3.x

Symprowire is a PHP MVC Framework based and built on Symfony using ProcessWire 3.x as DBAL and Service-Provider.

It acts as a Drop-In Replacement Module to handle the Request/Response outside the ProcessWire Admin. 

Symprowire's main Goal is to give an easy path to follow an MVC Approach during development with ProcessWire and open up the available eco-system.

**To learn more about Symprowire**
- check out the Wiki: https://github.com/Luis85/symprowire/wiki
- check out the Blog Tutorial: https://github.com/Luis85/symprowire/wiki/Symprowire-Blog-Tutorial
- check out the Demo Application: https://github.com/Luis85/symprowire-demo
- check out the Project Board for v1: https://github.com/Luis85/symprowire/projects/1

## Requirements

- PHP ^7.4
- Fresh ProcessWire ^3.0.181 with a Blank Profile 
- Composer 2 (v1 should work, not recommended)
- The usual Symfony Requirements

## Features

- Twig
- Dependency Injection
- Monolog for Symprowire
- Support for .env
- YAML Configuration
- Symfony Console and Console Commands
- Symfony Webprofiler
- Full ProcessWire access inside your Controller and Services
- Webpack Encore support

## Installation

- Create a new ProcessWire 3.0.181 Installation using the Blank Profile
- Copy Symprowire to `site/modules` 
- Open up a Terminal and `cd site/modules/symprowire`
- `composer install`
- install the module via ProcessWire Admin

> Heads up
> 
> To use Webpack Encore follow the 'Webpack Encore' section further down this Document  

### The Installer

Symprowire ships with a custom Installer Class to execute functions after Symprowire's Installation. The Installer is a great place to create a barbone application structure inside ProcessWire. Creating Templates, Pages, Fields etc.

You can find the Installer in `site/modules/symprowire/src/Installer.php`

`Installer->run()` will be executed automatically on Module Installation.
> Heads up
> 
> The $installer->run() method is called by Symprowire directly after internal installation but still inside ProcessWire's Module installation process

## Usage

Symprowire follows Symfony best practices and ships with a HomeController to get you started.
Your Business logic will live in `site/modules/symprowire/src` and has to follow PSR-4 Naming conventions. 

To create a new Controller just extend the `Symprowire/AbstractController` add a new route via Annotation and create the corresponding Twig Template in `site/templates/twig`

### The AbstractController 

The AbstractController itself extends `Symfony\Bundle\FrameworkBundle\Controller\AbstractController` to give you Symonfy Helper functions

In addition you will get the following ProcessWire Variables inside your Controller

- `$this->page = wire('page');`
- `$this->user = wire('user');`
- `$this->urls = wire('urls');`
- `$this->input = wire('input');`
- `$this->fields = wire('fields');`
- `$this->session = wire('session');`
- `$this->database = wire('database');`
- `$this->sanitizer = wire('sanitizer');`
- `$this->templates = wire('templates');`
- `$this->paths = wire('config')->paths;`
- `$this->urls = wire('config')->urls;`

- `$this->logger = ProcessWireLoggerService;` ($log implemented as Service)
- `$this->pages = $pagesRepository;` ($pages implemented as Repository)
- `$this->modules = $modulesRepository;` ($modules implemented as Repository)

**To gain full access to ProcessWire use** `$this->wire($name)` **inside your Controller**

>You should try to wrap Collections into own Repositories based on the Template you use.

>You should try to wrap Modules into own Services to make them accessable for DependencyInjection and easier Testing.

>Symprowire Repositories and Services implement their own Interfaces. You should follow this path as it would make testing your Application a lot easier in the long run.

### Services and Autowiring

Symprowire Services will get autowired by Symfony and are thus available for DI. 
You can find Symprowire Interfaces and Services in `site/modules/symprowire/lib`.

>**You should not edit these files as they are the core glue between Symfony and ProcessWire.** 

To create your own Services `site/modules/symprowire/src` is the place to add them. 
This directory is watched by Symfony and will make your Services accessable for autowiring.

>You have to use the `\App` Namespace for your Services and Controllers. 

## Webpack Encore
To bundle your Frontend you can use `symfony/webpack-encore`.

### Requirements
- yarn
- npm

To activate Webpack Encore
- `cd site/modules/symprowire`
- `yarn install`
- `yarn build`

Build files will be put into `site/modules/symprowire/public/build`.
Encore is preconfigured to serve his encore assets trough the public dir.

All you have to do is add `{{ encore_entry_link_tags('app') }}` and `{{ encore_entry_script_tags('app') }}` to your twig template.

For a working example check out `site/modules/symprowire/lib/twig`

## Namespaces

`\Symprowire` - lib Namespace used for Module/Framework Services. **Do not edit**

`\App` - Userland for your Controllers, Services, Repositories etc...

## Registered Services and Repositories

- PagesRepository
- UserRepository
- ModulesRepository

- ProcessWireService // The main Service to interact with ProcessWire Data
- ProcessWireMailerService
- ProcessWireLoggerService
