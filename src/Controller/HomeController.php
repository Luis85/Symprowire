<?php

namespace App\Controller;


use Symprowire\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symprowire\Interfaces\ProcessWireLoggerServiceInterface;
use Symprowire\Interfaces\UserRepositoryInterface;

class HomeController extends AbstractController
{

    /**
     * This Route does have a corresponding Page and therefore $this->page is populated accordingly
     * We Inject the UserRepository and ProcessWireLoggerService for Demonstration
     * As we are using Interfaces we use them instead of the Service for better test capabilities and CodeCompletition
     *
     * @Route("/", name="app_home")
     */
    public function index(UserRepositoryInterface $users, ProcessWireLoggerServiceInterface $loggerService): Response {

        // get the Homepage from the PagesRepository, available as Helper from AbstractController
        $title = $this->pages->getById(1)->title;

        // log an Error for funsies
        $loggerService->error('I am a test error log');

        // Make some Vars available to the Frontend, $user and $session are automatically provided to Twig
        $vars = [
            'page' => $this->page,
            'pages' => $this->pages,
            'input' => $this->input,
            'modules' => $this->modules,
            'users' => $users,
        ];

        return $this->render('home/index.html.twig', [
            'title' => $title,
            'vars' => $vars,
        ]);
    }

    /**
     * This Route does not have a corresponding Page
     * So keep in mind $this->page will be populated with $page->home
     *
     * @Route("/hello", name="app_hello")
     */
    public function hello(): Response {

        return $this->render('home/hello.html.twig', [
            'title' => 'Symprowire says',
            'msg' => 'Hello '.$this->user->name,
        ]);
    }

}
