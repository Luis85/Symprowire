<?php

namespace App\Controller;


use App\Symprowire\AbstractController;
use ProcessWire\WireException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     * @throws WireException
     */
    public function index(): Response {

        $title = $this->pages->get(1)->title;

        $vars = [
            'page' => $this->page,
            'pages' => $this->pages,
            'input' => $this->input,
            'session' => $this->session,
            'modules' => $this->modules,
        ];

        return $this->render('home/index.html.twig', [
            'title' => $title,
            'vars' => $vars,
        ]);
    }
}
