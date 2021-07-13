<?php


namespace Symprowire\Repository;


use Symprowire\Interfaces\ModulesRepositoryInterface;
use ProcessWire\Wire;

class ModulesRepository extends Wire implements ModulesRepositoryInterface
{
    public function get(string $name) {
        return $this->wire('modules')->get($name);
    }
}
