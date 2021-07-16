<?php


namespace Symprowire\Repository;


use Symprowire\Interfaces\ModulesRepositoryInterface;
use Symprowire\Interfaces\ProcessWireServiceInterface;

class ModulesRepository implements ModulesRepositoryInterface
{
    protected $processWire;

    public function __construct(ProcessWireServiceInterface $processWire) {
        $this->processWire = $processWire;
    }

    public function get(string $name) {
        return $this->processWire->get('modules')->get($name);
    }

    public function __toString(): string {
        return get_class($this);
    }
}
