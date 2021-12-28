<?php

namespace Symprowire;

use Exception\SymprowireExecutionException;
use Interfaces\SymprowireInterface;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Exception;
use ProcessWire\ProcessWire;
use Symprowire\Engine\SymprowireRuntime;
use Symprowire\Exception\SymprowireRequestFactoryException;

/**
 * Symprowire - a PHP MVC Framework for ProcessWire
 * ------------------------------------------------
 *
 * This Class is mainly responsible to execute the Kernel with a given ProcessWire instance.
 * Execution will return the processed Kernel with attached Request and Response
 * To get the processed Response Data as a string, just call the ::render()
 *
 */
class Symprowire implements SymprowireInterface
{

    protected Kernel $kernel;
    protected array $params;
    protected bool $finished = false;

    /**
     * TODO: add the native ProcessWire File Renderer as option
     *
     * @throws SymprowireExecutionException
     * @throws SymprowireRequestFactoryException
     */
    public function execute(ProcessWire $processWire, array $params = []): Kernel {

        $this->params = [
            'project_dir' => $processWire->config->paths->site,
            'renderer' => 'twig',
            'test' => false,
            'disable_dotenv' => true
        ];
        $params =  array_merge($this->params, $params);
        $this->params = $params;
        try {
            /**
             * Create a Symprowire callable from the Symprowire/Kernel, injecting ProcessWire and create a new Runtime
             */
            $symprowire = function ($processWire, $params) {
                return new Kernel($processWire, $params);
            };
            $runtime = new SymprowireRuntime($params);

            /**
             * Resolve the SymprowireKernel, set env arguments, execute and get the created Response
             * we send our Kernel as callable to the runtime and execute the Kernel
             * the called Symprowire/Runner will handle the callable Kernel and attach the result to the Runner
             */
            [$symprowire, $args] = $runtime->getResolver($symprowire)->resolve();
            $symprowire = $symprowire(...$args);
            $runtime->getRunner($symprowire)->run();
            $this->kernel = $runtime->getExecutedRunner()->getKernel();
            $this->finished = true;
            return $this->kernel;
        } catch(Exception $exception) {
            throw new SymprowireExecutionException('Symprowire Execution Failed', 200, $exception);
        }
    }

    /**
     *
     * @return string
     */
    #[Pure]
    public function render(): string {
        return $this->kernel->getResponse()->getContent();
    }
}
