<?php


namespace App\Symprowire;


use function ProcessWire\wire;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    protected function wire(string $name) {
        return wire($name);
    }
}
