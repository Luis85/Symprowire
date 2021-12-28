<?php namespace ProcessWire;

use Exception;
use Symprowire\Engine\SymprowireRuntime;
use Symprowire\Kernel;

/**
 * This is the FrontController of Symprowire
 *
 * We use our SymprowireRuntime to execute Symprowire and return the Response Content to get ProcessWire back in charge.
 * First we will create a callable to execute the Symprowire/Kernel and injecting the current ProcessWire instance into the Dependency Container.
 * Every Exception thrown inside Symprowire should be handled by the Framework.
 * If the Framework itself fails, ProcessWire could catch up
 *
 */
try {

    // We need the composer autoloader first
    require_once($this->config->paths->site . 'vendor/autoload.php');

    /**
     * lets create a Symprowire callable from the Symprowire/Kernel, injecting ProcessWire and create a new Runtime
     */
    $symprowire = function (ProcessWire $wire) {
        return new Kernel($wire);
    };
    $runtime = new SymprowireRuntime(['project_dir' => $this->config->paths->site]);

    /**
     * Resolve the SymprowireKernel, set env arguments, execute and get the created Response
     * we send our Kernel as callable to the runtime and execute the Kernel
     * the called Symprowire/Runner will handle the callable Kernel and attach the result to the Runner
     */
    [$symprowire, $args] = $runtime->getResolver($symprowire)->resolve();
    $symprowire = $symprowire(...$args);
    $runtime->getRunner($symprowire)->run(); // <-- this handles the Kernel
    $symprowire = $runtime->getExecutedRunner()->getKernel(); // <-- this gives the processed Kernel

    /**
     * Dump the executed Kernel into Tracy for inspection and debugging
     * The executed Kernel will expose the processed Request, created Response and execution time
     */
    if($this->modules->isInstalled('TracyDebugger') && $this->config->debug) {
        bd($symprowire, 'Symprowire / Executed Kernel', [4]);
    }

    /**
     * Finally get the Response Content as a string and return it back to ProcessWire
     */
    return $symprowire->getResponse()->getContent();

}
/**
 * We will catch every Exception thrown by the Kernel itself and serve a 404 if not in debug.
 * Error Handling is now served by ProcessWire again
 */
catch (Exception $exception) {
    $this->log->error($exception->getMessage());
    if($this->config->debug) throw $exception;
    throw new Wire404Exception();
}

