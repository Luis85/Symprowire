<?php


namespace Symprowire\Interfaces;


use Psr\Log\LoggerInterface;

interface ProcessWireLoggerServiceInterface extends LoggerInterface
{
    // create a new Entry to site/assets/logs/message.txt
    public function message(string $text);

    // create a new Entry to a $name.txt inside site/assets/logs/
    // we use this method to follow LoggerInterface
    public function save(string $name, string $text);
}
