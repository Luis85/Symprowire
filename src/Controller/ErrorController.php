<?php


namespace App\Controller;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

class ErrorController
{
    public function exception(FlattenException $exception)
    {
        $msg = 'Symprowire Error : ('.$exception->getMessage().')';

        return new Response($msg, $exception->getStatusCode());
    }
}
