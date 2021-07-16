<?php


namespace Symprowire\Repository;


use Symprowire\Interfaces\PagesRepositoryInterface;

use ProcessWire\PageArray;
use ProcessWire\Page;

class PagesRepository extends AbstractPagesRepository implements PagesRepositoryInterface
{
    public function find(string $selector): PageArray {
        return $this->pages->findMany($selector);
    }

    public function getById(int $id): Page {
        return $this->pages->get($id);
    }

    public function get(string $selector): Page {
        return $this->pages->get($selector);
    }
}
