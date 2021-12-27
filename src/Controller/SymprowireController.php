<?php

namespace Symprowire\Controller;

use ProcessWire\Config;
use ProcessWire\Page;
use ProcessWire\Pages;
use ProcessWire\ProcessWire;
use ProcessWire\Session;
use ProcessWire\User;
use ProcessWire\WireInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symprowire\Engine\SymprowireResponse;
use Symprowire\Service\ProcessWireService;


/**
 * The Symprowire Base Controller
 *
 * The Main Class to extend for the Developer to Render a View and compose the needed Data.
 */
abstract class SymprowireController extends AbstractController
{

    protected User $user;
    protected Page $page;
    protected Pages $pages;
    protected Config $config;
    protected Session $session;
    protected WireInput $input;
    protected ProcessWire $wire;
    protected Request $request;
    protected ProcessWireService $pwService;

    /**
     * @param ProcessWire $processWire
     * @param ProcessWireService $processWireService
     *
     * Make ProcessWire available for all extending Controllers and make often used vars available
     * Inject the ProcessWireService to get some shortcuts
     */
    public function __construct(ProcessWire $processWire, ProcessWireService $processWireService) {
        $this->wire = $processWire;
        $this->page = $this->wire->page;
        $this->pages = $this->wire->pages;
        $this->input = $this->wire->input;
        $this->session = $this->wire->session;
        $this->user = $this->wire->user;
        $this->config = $this->wire->config;
        $this->pwService = $processWireService;
    }

    /**
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     * @return Response
     *
     * Render the requested view with Twig
     * We add often used ProcessWire Vars to the template and merge the Developer params
     * @TODO move this into Twig Globals
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response {

        $templateData = ['templateData' => []];
        $app = [
            'config' => $this->wire,
            'homepage' => $this->pages->get(1),
            'admin' => $this->pages->get(2),
            'user' => $this->user,
            'view' => $view,
        ];
        $globals = [
            'app' => $app,
            'page' => $this->page,
        ];
        $templateData['templateData'] = array_merge($globals, $parameters);

        $content = $this->renderView($view, $templateData['templateData']);

        if (null === $response) {
            $response = new SymprowireResponse();
        }

        $response->setContent($content);

        return $response;
    }

}
