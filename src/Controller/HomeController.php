<?php

namespace App\Controller;


use Symprowire\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symprowire\Interfaces\UserRepositoryInterface;

class HomeController extends AbstractController
{

    /**
     * This Route does have a corresponding Page and therefore $this->page is populated accordingly
     * We Inject the UserRepository for Demonstration purposes as Symprowire knows about the current User inside Controllers
     * But to get all Users we want to use the Repository as a central data-point
     * As we are using Interfaces we use them instead of the Service for better test capabilities and CodeCompletition
     *
     * @Route("/", name="app_home")
     */
    public function index(UserRepositoryInterface $users): Response {

        // get the Homepage from the PagesRepository, available as Helper from AbstractController
        // this is just for demonstration as the homepage is an actual path in ProcessWire we could have used $this->page
        $title = $this->pages->getById(1)->title;

        // Make some Vars available to the Frontend
        // $user, $page and $session are automatically provided to Twig
        // Be carefull with using $this->page as it might not give you the page you are looking for
        $vars = [
            'users' => $users,
            'input' => $this->input,
            'pages' => $this->pages,
            'modules' => $this->modules,
        ];
        // Twigs default path for Templates to look at is site/templates/twig
        // as fallback Symprowire ships with a twig-directory in lib/twig to serve a fresh installed Project
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
