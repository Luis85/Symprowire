<?php

namespace Symprowire\Interfaces;

use ProcessWire\ProcessWire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface SymprowireKernelInterface
{
    public function setResponse(Response $response): self;

    public function getResponse(): ?Response;

    public function setRequest(Request $request): self;

    public function getRequest(): ?Request;

    public function getProcessWire(): ?ProcessWire;

    public function getExecutionTime(): string;
}
