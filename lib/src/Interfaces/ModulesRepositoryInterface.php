<?php


namespace Symprowire\Interfaces;


interface ModulesRepositoryInterface
{
    public function get(string $name);

    public function __toString(): string;
}
