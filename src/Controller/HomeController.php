<?php

namespace App\Controller;


use Symprowire\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symprowire\Repository\UserRepository;

class HomeController extends AbstractController
{

    /**
     * This Route does have a corresponding Page and therefore $this->page is populated accordingly
     *
     * @Route("/", name="app_home")
     */
    public function index(UserRepository $users): Response {

        $title = $this->pages->getById(1)->title;

        $vars = [
            'page' => $this->page,
            'pages' => $this->pages,
            'input' => $this->input,
            'session' => $this->session,
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
     * $this->page will return the HomePage
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
