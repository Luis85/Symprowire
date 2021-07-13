<?php

namespace App\Controller;


use ProcessWire\WireException;
use Symprowire\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symprowire\Repository\UserRepository;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="app_home")
     * @throws WireException
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

}
