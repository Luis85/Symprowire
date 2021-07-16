<?php


namespace Symprowire\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;
use Symfony\Component\HttpFoundation\Response;

use Symprowire\Interfaces\AbstractControllerInterface;
use Symprowire\Interfaces\ModulesRepositoryInterface;
use Symprowire\Interfaces\PagesRepositoryInterface;
use Symprowire\Interfaces\ProcessWireLoggerServiceInterface;
use Symprowire\Interfaces\ProcessWireServiceInterface;

abstract class AbstractController extends SymfonyController implements AbstractControllerInterface
{

    protected $page;
    protected $user;
    protected $urls;
    protected $input;
    protected $paths;
    protected $fields;
    protected $session;
    protected $database;
    protected $templates;
    protected $sanitizer;

    protected PagesRepositoryInterface $pages;
    protected ModulesRepositoryInterface $modules;

    private ProcessWireServiceInterface $processWire;
    protected ProcessWireLoggerServiceInterface $logger;

    public function __construct(
        ProcessWireServiceInterface $processWire,
        ProcessWireLoggerServiceInterface $loggerService,
        ModulesRepositoryInterface $modulesRepository,
        PagesRepositoryInterface $pagesRepository
    ) {

        $this->pages = $pagesRepository;
        $this->logger = $loggerService;
        $this->modules = $modulesRepository;
        $this->processWire = $processWire;

        $this->page = $this->processWire->get('page');
        $this->user = $this->processWire->get('user');
        $this->urls = $this->processWire->get('urls');
        $this->input = $this->processWire->get('input');
        $this->fields = $this->processWire->get('fields');
        $this->session = $this->processWire->get('session');
        $this->database = $this->processWire->get('database');
        $this->sanitizer = $this->processWire->get('sanitizer');
        $this->templates = $this->processWire->get('templates');

        $this->paths = $this->processWire->get('config')->paths;
        $this->urls = $this->processWire->get('config')->urls;
    }

    // provide direct access to ProcessWire inside the Controller
    protected function wire(string $name) {
        return $this->processWire->get($name);
    }

    // we extend the render() function to create ProcessWire known Globals
    protected function render(string $view, array $parameters = [], $response = null): Response {

        $vars = [
            'user' => $this->user,
            'session' => $this->session,
        ];
        $parameters = array_merge($vars, $parameters);

        return parent::render($view, $parameters, $response);
    }
}
