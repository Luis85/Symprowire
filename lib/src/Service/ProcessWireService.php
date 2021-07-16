<?php


namespace Symprowire\Service;


use Symprowire\Interfaces\ProcessWireServiceInterface;
use function ProcessWire\wire;

/*
 * ProcessWireService acts as a Wrapper to reduce direct \ProcessWire\wire() function calls trough the Application
 * Use this Service to gain access to underlying ProcessWire Data.
 * If you need more ProcessWire functionality in your Application consider to extend this Service with your own implementation.
 */
class ProcessWireService implements ProcessWireServiceInterface
{
    public function get(string $name)
    {
        return wire($name);
    }
}
