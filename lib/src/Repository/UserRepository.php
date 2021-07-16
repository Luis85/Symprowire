<?php


namespace Symprowire\Repository;

use Symprowire\Interfaces\ProcessWireServiceInterface;
use Symprowire\Interfaces\UserRepositoryInterface;
use Processwire\User;
use Processwire\PageArray;

class UserRepository implements UserRepositoryInterface
{
    private $users;

    public function __construct(ProcessWireServiceInterface $processWire) {
        $this->users = $processWire->get('users');
    }

    public function getByEmail(string $email): User {
        return $this->users->get('email='.$email);
    }

    public function getByName(string $name): User {
        return $this->users->get('name='.$name);
    }

    public function get(string $selector): User {
        return $this->users->get($selector);
    }

    public function getById(int $id): User {
        return $this->users->get($id);
    }

    public function find(string $selector): PageArray {
        return $this->users->findMany($selector);
    }

    public function __toString(): string {
        return get_class($this);
    }
}
