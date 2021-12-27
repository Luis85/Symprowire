<?php

namespace Symprowire\Engine;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symprowire\Interfaces\SymprowireKernelInterface;
use Symprowire\Kernel;

/**
 * The SymprowireKernelRunner
 *
 * executes the configured SymprowireKernel
 * attaches the Response to the Kernel to make it available to ProcessWire
 */
class SymprowireKernelRunner implements RunnerInterface
{
    private SymprowireKernelInterface $kernel;
    private Request $request;

    public function __construct(SymprowireKernelInterface $kernel, Request $request)
    {
        $this->kernel = $kernel;
        $this->request = $request;
    }

    /**
     * @throws Exception
     *
     * handles the Kernel to build the Response
     * This Runner sets the Response on the Kernel, to render the content you need to process the executed Kernel
     *
     */
    public function run(): int
    {
        $response = $this->kernel->handle($this->request);
        $this->request->attributes->set('_processed', hrtime(true) );
        $this->kernel->setRequest($this->request);
        $this->kernel->setResponse($response);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($this->request, $response);
        }

        return 0;
    }

    /**
     * @return Kernel
     *
     * Open up the Runner to make the Kernel available after execution
     *
     */
    public function getKernel(): Kernel {
        return $this->kernel;
    }
}

