<?php namespace ProcessWire;

use Exception;
use Symprowire\Symprowire;


/**
 * This is the Symprowire FrontController
 *
 * Every Exception thrown inside Symprowire should be handled by the Framework.
 * If the Framework itself fails, ProcessWire could catch up
 *
 */

require_once($this->config->paths->site . 'vendor/autoload.php');

try {
    $symprowire = new Symprowire();
    $symprowire->execute($this->wire);
    if($this->modules->isInstalled('TracyDebugger') && $this->config->debug) {
        bd($symprowire, 'Symprowire / Executed Kernel', [4]);
    }
    return $symprowire->render();
}
/**
 * We will catch every Exception thrown by Symprowire and serve a 404 if not in debug.
 * Error Handling is now served by ProcessWire again
 */
catch (Exception $exception) {
    $this->log->error($exception->getMessage());
    if($this->config->debug) throw $exception;
    throw new Wire404Exception();
}

