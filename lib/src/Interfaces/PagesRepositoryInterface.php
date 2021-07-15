<?php


namespace Symprowire\Interfaces;

use ProcessWire\PageArray;
use ProcessWire\Page;

interface PagesRepositoryInterface
{
    public function find(string $selector): PageArray;

    public function getById(int $id): Page;

    public function get(string $selector): Page;
}
