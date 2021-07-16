<?php


namespace Symprowire\Service;


use Symprowire\Interfaces\ProcessWireLoggerServiceInterface;

use Symprowire\Interfaces\ProcessWireServiceInterface;

class ProcessWireLoggerService implements ProcessWireLoggerServiceInterface
{
    private $logger;

    public function __construct(ProcessWireServiceInterface $processWire ) {
        $this->logger = $processWire->get('log');
    }

    public function message(string $text)
    {
        $this->logger->message($text);
    }

    public function save(string $name, string $text)
    {
        $this->logger->save($name, $text);
    }

    public function emergency($message, array $context = array())
    {
        $this->logger->save('emergency', $message);
    }

    public function alert($message, array $context = array())
    {
        $this->logger->save('alert', $message);
    }

    public function critical($message, array $context = array())
    {
        $this->logger->save('critical', $message);
    }

    public function notice($message, array $context = array())
    {
        $this->logger->save('notice', $message);
    }

    public function info($message, array $context = array())
    {
        $this->logger->save('info', $message);
    }

    public function debug($message, array $context = array())
    {
        $this->logger->save('debug', $message);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->save('log-'.$level, $message);
    }

    public function error($message, array $context = array())
    {
        $this->logger->error($message);
    }

    public function warning($message, array $context = array())
    {
        $this->logger->warning($message);
    }
}
