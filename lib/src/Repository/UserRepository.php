<?php


namespace Symprowire\Repository;

use Symprowire\Interfaces\UserRepositoryInterface;
use Processwire\User;
use Processwire\Users;
use ProcessWire\Wire;

class UserRepository extends Wire implements UserRepositoryInterface
{
    private $users;

    public function __construct() {
        $this->users = $this->wire('users');
    }

    public function getByEmail(string $email): User {
        return $this->users->get('email='.$email);
    }

    public function getById(int $id): User {
        return $this->users->get($id);
    }

    public function find(string $selector): Users {
        return $this->users->findMany($selector);
    }
}
