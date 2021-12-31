<?php

namespace Symprowire\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symprowire\Engine\SymprowireResponse;

/**
 * Main Testable Controller to get a Response if testing the Runtime etc.
 */
class TestController
{
    #[Route('/_symprowire_test', name: '_symprowire_test_index')]
    public function index(): Response {
        $response = new SymprowireResponse();
        $response->setContent('controller.responded');
        return $response;
    }
}
