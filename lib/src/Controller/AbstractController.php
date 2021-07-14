<?php


namespace Symprowire\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;

use Symfony\Component\HttpFoundation\Response;
use Symprowire\Interfaces\AbstractControllerInterface;
use Symprowire\Interfaces\ProcessWireLoggerServiceInterface;
use Symprowire\Repository\ModulesRepository;
use Symprowire\Repository\PagesRepository;

use ProcessWire\Wire;
use ProcessWire\Config;
use ProcessWire\Fields;
use ProcessWire\Fieldtypes;
use ProcessWire\Modules;
use ProcessWire\Notices;
use ProcessWire\Page;
use ProcessWire\Pages;
use ProcessWire\Permissions;
use ProcessWire\ProcessWire;
use ProcessWire\Roles;
use ProcessWire\Sanitizer;
use ProcessWire\Session;
use ProcessWire\Templates;
use ProcessWire\User;
use ProcessWire\Users;
use ProcessWire\WireDatabasePDO;
use ProcessWire\WireDateTime;
use ProcessWire\WireFileTools;
use ProcessWire\WireHooks;
use ProcessWire\WireInput;
use ProcessWire\WireMailTools;

use function ProcessWire\wire;

abstract class AbstractController extends SymfonyController implements AbstractControllerInterface
{

    protected $input;
    protected $session;
    protected $page;
    protected $user;
    protected $sanitizer;
    protected $urls;
    protected $fields;
    protected $database;
    protected $templates;
    protected $paths;

    protected ProcessWireLoggerServiceInterface $logger;

    protected PagesRepository $pages;
    protected ModulesRepository $modules;

    public function __construct(
        ProcessWireLoggerServiceInterface $loggerService,
        ModulesRepository $modulesRepository,
        PagesRepository $pagesRepository
    ) {

        $this->page = wire('page');
        $this->user = wire('user');
        $this->urls = wire('urls');
        $this->input = wire('input');
        $this->fields = wire('fields');
        $this->session = wire('session');
        $this->database = wire('database');
        $this->sanitizer = wire('sanitizer');
        $this->templates = wire('templates');

        $this->logger = $loggerService;

        $this->paths = wire('config')->paths;

        $this->pages = $pagesRepository;
        $this->modules = $modulesRepository;

    }

    // provide direct access to ProcessWire inside the Controller
    protected function wire(string $name) {
        return wire($name);
    }

    // we extend the render() function to create ProcessWire known Globals
    protected function render(string $view, array $parameters = [], $response = null): Response {

        $vars = [
            'user' => $this->user,
            'page' => $this->page,
            'session' => $this->session,
        ];
        $parameters = array_merge($vars, $parameters);

        return parent::render($view, $parameters, $response);
    }
}
