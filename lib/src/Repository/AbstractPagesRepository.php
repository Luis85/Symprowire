<?php


namespace Symprowire\Repository;


use Symprowire\Interfaces\AbstractPagesRepositoryInterface;
use Symprowire\Interfaces\ProcessWireServiceInterface;
use ProcessWire\Pages;

/*
 * Use this class as a base to extend when creating your own Repository or use the PagesRepository Class. Both will give you the needed tools for easy data access.
 */
abstract class AbstractPagesRepository implements AbstractPagesRepositoryInterface
{
    protected Pages $pages;

    public function __construct(ProcessWireServiceInterface $processWire) {
        $this->pages = $processWire->get('pages');
    }

    public function __toString(): string {
        return get_class($this);
    }
}
