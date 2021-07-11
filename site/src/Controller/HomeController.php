<?php


namespace App\Controller;


use App\Symprowire\BaseController;
use ProcessWire\Wire404Exception;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends BaseController
{

    /**
     * @throws Wire404Exception
     */
    public function index(): Response {

        return $this->render('home/index', ['title' => 'Symprowire - ProcessWire meets Symfony']);
    }
}
