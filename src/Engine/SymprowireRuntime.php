<?php

namespace Symprowire\Engine;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Runtime\Internal\MissingDotenv;
use Symfony\Component\Runtime\Internal\SymfonyErrorHandler;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;
use Symprowire\Exception\SymprowireRequestFactoryException;
use Symprowire\Interfaces\SymprowireKernelInterface;

/**
 * The SymprowireRuntime
 *
 * executes our Kernel trough the KernelRunner
 *
 */
class SymprowireRuntime extends SymfonyRuntime
{

    protected ?SymprowireKernelRunner $runner = null;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * @throws SymprowireRequestFactoryException
     *
     * Create the Request and return the Kernel Runner
     * unlike the Symfony runtime we do not exit out after execution as our KernelRunner will atach the Response to the Kernel for the ProcessWire handshake
     *
     */
    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof SymprowireKernelInterface) {
            $this->runner = new SymprowireKernelRunner($application, SymprowireRequest::createSympro($application->getProcessWire()));
            return $this->runner;
        }
        return parent::getRunner($application);
    }

    public function getExecutedRunner(): SymprowireKernelRunner {
        return $this->runner;
    }
}
