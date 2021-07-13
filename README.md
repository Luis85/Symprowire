# Symprowire - PHP MVC Framework for ProcessWire 3.x

Symprowire is a PHP MVC Framework based on Symfony 5.3 using ProcessWire 3.x as DBAL.

## Requirements
- PHP ^7.4
- ProcessWire ^3.0.181 with a Blank Profile 
- Composer
- The usual Symfony Requirements

## Features
- Twig
- Dependency Injection
- Monolog
- Support for .env
- YAML Configuration
- Symfony Console and Console Commands
- Symfony Webprofiler
- Full ProcessWire Modules access inside your Controller

## Installation
- Create a new ProcessWire 3.0.181 Installation using the Blank Profile
- add Symprowire to site/modules 
- `cd site/modules/symprowire`
- `composer install`
- after composer is finished, install the module via ProcessWire Admin

## Usage

Symprowire follows Symfony best practices and ships with a HomeController to get you started.
Your Business logic will live in site/modules/symprowire/src and has to follow PSR-4 Naming conventions. 

To create a new Controller just extend the Symprowire/AbstractController add a new route via Annotation and create the corresponding Twig Template in site/templates/twig

### The AbstractController 

The AbstractController itself extends Symfony\Bundle\FrameworkBundle\Controller\AbstractController to give you Symonfy Helper functions
In addition you will get the following ProcessWire Variables
- $page
- $pages (implemented as PagesRepository)
- $input
- $session
- $modules (implemented as ModulesRepository)
- $user 

To use them in your Controller just call them like `$this->user` 
Symprowire Repositories and Services implement their own Interface to ease development.

### Services and Autowiring

Symprowire Services will get autowired by Symfony and are thus available for DI. 
You cann find Symprowire Interfaces and Services in site/modules/symprowire/lib 
You should not edit these files as they are the core glue between Symfony and ProcessWire. 

To create your own Services site/modules/symprowire/src is the place to add them. 
This directory is watched by Symfony and will make your Services accessable for autowiring.

You have to use the \App Namespace for your Services and Controllers. 

## Namespaces
\Symprowire - lib Namespace used for Module/Framework Services. Do not edit
\App - userland
