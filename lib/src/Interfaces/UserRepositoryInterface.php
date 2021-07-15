<?php


namespace Symprowire\Interfaces;

use ProcessWire\User;
use Processwire\PageArray;

interface UserRepositoryInterface
{
    public function getByEmail(string $email): User;

    public function getById(int $id): User;

    public function find(string $selector): PageArray;
}
