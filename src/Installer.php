<?php

/**
 * The Installer->run() is called after Symprowire finished his Installation
 * This is a great place to add a basic Page Strucutre, create Templates and Fields
 */

namespace App;

use Symprowire\Interfaces\InstallerInterface;
use Symprowire\Interfaces\ProcessWireServiceInterface;

class Installer implements InstallerInterface
{
    private ProcessWireServiceInterface $processWire;

    // make ProcessWire available as a Service
    public function __construct(ProcessWireServiceInterface $processWire) {
        $this->processWire = $processWire;
    }
    /*
     * The run() method is called by Symprowire directly after internal installation but still inside ProcessWire's Module installation process
     * We use ProcessWire's own logger instead of a Service, due to the Environment the Installer is called.
     */
    public function run(): bool {
        $logger = $this->processWire->get('log');
        $logger->message('Symprowire Installed');
        return true;
    }
}
