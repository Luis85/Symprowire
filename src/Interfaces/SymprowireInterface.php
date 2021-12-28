<?php

namespace Interfaces;

use ProcessWire\ProcessWire;
use Symprowire\Kernel;

interface SymprowireInterface
{
    public function execute(ProcessWire $processWire, array $params = []): Kernel;

    public function render(): string;
}
