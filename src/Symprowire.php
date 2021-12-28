<?php

namespace Symprowire;

use Exception\SymprowireExecutionException;
use Interfaces\SymprowireInterface;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Exception;
use ProcessWire\ProcessWire;
use Symprowire\Engine\SymprowireRuntime;
use Symprowire\Exception\SymprowireRequestFactoryException;

class Symprowire implements SymprowireInterface
{

    protected Kernel $kernel;

    /**
     * @throws SymprowireRequestFactoryException
     * @throws SymprowireExecutionException
     */
    public function execute(ProcessWire $wire, bool $test = false): Kernel {
        try {
            /**
             * Create a Symprowire callable from the Symprowire/Kernel, injecting ProcessWire and create a new Runtime
             */
            $symprowire = function ($wire) {
                return new Kernel($wire);
            };
            $runtime = new SymprowireRuntime(['project_dir' => $wire->config->paths->site]);

            /**
             * Resolve the SymprowireKernel, set env arguments, execute and get the created Response
             * we send our Kernel as callable to the runtime and execute the Kernel
             * the called Symprowire/Runner will handle the callable Kernel and attach the result to the Runner
             */
            [$symprowire, $args] = $runtime->getResolver($symprowire)->resolve();
            $symprowire = $symprowire(...$args);
            $runtime->getRunner($symprowire)->run();
            $this->kernel = $runtime->getExecutedRunner()->getKernel();
            return $this->kernel;
        } catch(Exception $exception) {
            throw new SymprowireExecutionException('Symprowire Execution Failed', 200, $exception);
        }
    }

    #[Pure]
    public function render(): string {
        return $this->kernel->getResponse()->getContent();
    }
}
