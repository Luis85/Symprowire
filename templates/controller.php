<?php namespace ProcessWire;

use Symprowire\Symprowire;


/**
 * This is the Symprowire FrontController
 * -----------------------------------------
 *
 * We require the composer autoloader
 * Add some parameters to Symprowire
 * Execute the App with the current ProcessWire instance
 * if ProcessWire is running in debug and TracyDebugger is installed we will dump the whole executed Kernel to Tracy
 * and echo a HTML string back to ProcessWire
 *
 */

require_once($this->config->paths->site . 'vendor/autoload.php');

$params = [
    'renderer' => 'twig',
];

$symprowire = new Symprowire($params);
$symprowire->execute($this->wire);

if($this->modules->isInstalled('TracyDebugger') && $this->config->debug) {
    bd($symprowire, 'Symprowire / Executed Kernel', [5]);
}

echo $symprowire->render();
