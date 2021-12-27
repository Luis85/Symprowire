<?php

namespace Symprowire\Controller;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @TODO Notify ProcessWire / TracyDebugger and bubble up the message
 * Symprowire Main Error Handler.
 */
class ErrorController
{
    public function exception(FlattenException $exception): Response
    {
        $msg = 'Something went wrong! ('.$exception->getMessage().')';

        return new Response($msg, $exception->getStatusCode());
    }
}
