<?php

namespace Interfaces;

use ProcessWire\ProcessWire;
use Symprowire\Kernel;

interface SymprowireInterface
{
    public function execute(ProcessWire $wire, bool $test = false): Kernel;

    public function render(): string;
}
