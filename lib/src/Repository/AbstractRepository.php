<?php


namespace Symprowire\Repository;


use Symprowire\Interfaces\AbstractRepositoryInterface;
use ProcessWire\Pages;
use ProcessWire\Wire;

abstract class AbstractRepository extends Wire implements AbstractRepositoryInterface
{
    protected Pages $pages;

    public function __construct() {
        $this->pages = $this->wire('pages');
    }
}
