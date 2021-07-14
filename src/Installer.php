<?php

/**
 * The Installer->run() is called after Symprowire finished his Installation
 * This is a great place to add a basic Page Strucutre, create Templates and Fields
 */

namespace App;

use ProcessWire\Wire;
use Symprowire\Interfaces\InstallerInterface;

class Installer extends Wire implements InstallerInterface
{
    /*
     *  the run() method is called by Symprowire directly after internal installation but still inside ProcessWire's Module installation process
     */
    public function run(): bool {

        return true;
    }
}
