<?php

namespace Symprowire\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Demo Controller
 *
 * index action is listening to /
 *
 * hello action is listening to ?_sympro=hello and can get a name var
 */
class HomeController extends SymprowireController
{

    public function index(): Response {
        return $this->render('home/index.html.twig');
    }

    public function hello(string $name = 'Anonymous'): Response {
        return $this->render('home/hello.html.twig', ['name' => $name]);
    }

    /**
     * Annotations work too
     *
     * @Route("/home/test", name="test")
     */
    public function text(string $name = 'Anonymous'): Response {
        return $this->render('home/hello.html.twig', ['name' => $name]);
    }

}
