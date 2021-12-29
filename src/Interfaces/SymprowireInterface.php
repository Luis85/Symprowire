<?php

namespace Symprowire\Interfaces;

use ProcessWire\ProcessWire;

interface SymprowireInterface
{
    public function execute(ProcessWire $processWire = null): SymprowireKernelInterface;

    public function render(): string;

    public function isReady(): bool;

    public function isExecuted(): bool;
}
