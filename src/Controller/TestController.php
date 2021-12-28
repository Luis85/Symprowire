<?php

namespace Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Main Testable Controller to get a Response if testing the Runtime etc.
 */
class TestController
{

    /**
     * Our main route to return a test Response
     *
     * @Route("/_test", name="test_index")
     */
    public function index(): Response {
        $response = new Response();
        $response->setContent('controller responded');
        return $response;
    }
}
