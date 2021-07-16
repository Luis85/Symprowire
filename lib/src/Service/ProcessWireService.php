<?php


namespace Symprowire\Service;


use Symprowire\Interfaces\ProcessWireServiceInterface;
use function ProcessWire\wire;

class ProcessWireService implements ProcessWireServiceInterface
{
    public function get(string $name)
    {
        return wire($name);
    }
}
