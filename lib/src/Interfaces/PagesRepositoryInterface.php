<?php


namespace Symprowire\Interfaces;

use ProcessWire\Pages;
use ProcessWire\Page;

interface PagesRepositoryInterface
{
    public function find(string $selector): Pages;

    public function getById(int $id): Page;

    public function get(string $selector): Page;
}
