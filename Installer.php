<?php

/**
 * The Installer->run() is called after Symprowire finished his Installation
 * This is a great place to add a basic Page Strucutre, create Templates and Fields
 */

namespace ProcessWire;


class Installer extends Wire
{
    /*
     * The run() method is called by Symprowire directly after internal installation but still inside ProcessWire's Module installation process
     * We use ProcessWire's own logger instead of a Service, due to the Environment the Installer is called.
     */
    public function run(): bool {
        $logger = $this->wire('log');
        $logger->message('Symprowire Installed');
        return true;
    }
}
