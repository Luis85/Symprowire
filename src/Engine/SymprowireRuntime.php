<?php

namespace Symprowire\Engine;

use ProcessWire\WireException;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;
use Symprowire\Exception\SymprowireRuntimeException;
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

    /**
     * @throws SymprowireRuntimeException
     * @throws WireException
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
        throw new SymprowireRuntimeException('This Runtime does not accept this Kernel', 200);
        //return parent::getRunner($application);
    }

    public function getExecutedRunner(): SymprowireKernelRunner {
        return $this->runner;
    }
}
