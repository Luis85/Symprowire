<?php

namespace Symprowire;

use ProcessWire\ProcessWire;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symprowire\Interfaces\SymprowireKernelInterface;

/**
 * The Symprowire Kernel
 * --------------------------
 *
 * A magic place. A new Symprowire Application will be spawned.
 * Based on the Symfony HttpKernel we will get a Request, attach ProcessWire to the Kernel, handle the processing and return a Response to ProcessWire's page->render
 * @TODO Kernel Lifecycle Description
 */
class Kernel extends BaseKernel implements SymprowireKernelInterface
{
    use MicroKernelTrait;

    protected ?ProcessWire $wire;

    protected ?Request $request = null;
    protected ?Response $response = null;
    protected string $executionTime = '';
    protected ?int $executionTimeRaw = null;

    /**
     * @param string $environment
     * @param bool $debug
     * @param ProcessWire|null $wire
     *
     * @TODO  to make the whole setup testable we have to make a ProcessWire Mock, otherwise every test against the business logic depends on the database
     * Running the Kernel with a Runtime will give as a testable Interface
     */
    public function __construct(string $environment, bool $debug, ProcessWire $wire = null)
    {
        $this->wire = $wire;
        parent::__construct($environment, $debug);
    }

    /**
     *
     * Create the Container
     * Inject ProcessWire in the DI Container if present
     * this will setup our configured synthetic service and make ProcessWire available in the System
     *
     */
    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        if($this->wire) {
            $this->container->set('processwire', $this->wire);
        }
    }

    /**
     * We open up the Kernel intentionally to make the executed Request and Response Objects available in a ProcessWire Environment.
     * We follow the standard Symfony Request - Process - Response Workflow but we do not want to terminate the Request as this is a responsibility of ProcessWire
     * These functions are not meant to be used outside a ProcessWire Template File
     * getResponse and getRequest will give you the corresponding Objects if the Kernel was executed by the SymprowireRuntime
     *
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response): self {
        $this->response = $response;
        $received = (int) $this->request->attributes->get('_received');
        $processed = (int) $this->request->attributes->get('_processed');

        $this->executionTimeRaw = $processed - $received;
        $this->executionTime = $this->getExecutionTime();
        return $this;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response {
        return $this->response;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request): self {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request {
        return $this->request;
    }

    /**
     * @return ProcessWire
     */
    public function getProcessWire(): ProcessWire {
        return $this->wire;
    }

    /**
     * @return string
     */
    public function getExecutionTime(): string {
        if($this->executionTimeRaw) {
            return ( (int) $this->executionTimeRaw / 1000000) . ' ms';
        }
        return '';
    }

}
