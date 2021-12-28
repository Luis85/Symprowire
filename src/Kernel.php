<?php

namespace Symprowire;

use ProcessWire\ProcessWire;
use ProcessWire\Wire;
use Symprowire\Engine\ProcessWireMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symprowire\Interfaces\SymprowireKernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

/**
 * The Symprowire Kernel
 * --------------------------
 *
 * A magic place. A new Symprowire Application will be spawned.
 * Based on the Symfony HttpKernel we will get a Request, attach ProcessWire to the Kernel, handle the processing and return a Response to ProcessWire's page->render
 * TODO Kernel Lifecycle Description
 */
class Kernel extends BaseKernel implements SymprowireKernelInterface
{
    use MicroKernelTrait;

    public const ENGINE = 'Symprowire';
    public const VERSION = '0.0.1';

    protected ?Request $request = null;
    protected ?Response $response = null;
    protected string $executionTime = '';
    protected ?int $executionTimeRaw = null;
    protected ?ProcessWire $wire;

    /**
     *
     * Construct The Symprowire Kernel
     * --------------------------
     *
     * Running the Kernel with a Runtime will give us a testable Interface but we are dependend on a ProcessWire instance
     * TODO to make the whole setup testable we have to make a ProcessWire Mock, otherwise every test against the business logic depends on the database
     *
     * @param ProcessWire|null $wire
     *
     */
    public function __construct(ProcessWire $wire = null)
    {
        if($wire) {
            $this->wire = $wire;
            $debug = $wire->config->debug;
        } else {
            $this->wire = null;
            $debug = true;
        }
        $environment =  $debug ? 'dev' : 'prod';

        parent::__construct($environment, (bool) $debug);
    }

    /**
     *
     * Create the Container
     * Inject ProcessWire in the DI Container if present
     * this will setup our configured synthetic service and make ProcessWire available in the System
     *
     * TODO: In order to use the console we have to fill the synthetic pw service with a mock. This should be refactored I guess
     *
     */
    protected function initializeContainer(): void
    {
        /**
         * TODO fix this...
         *
         * we use a Mock which extends Wire if wire is not set on construction. Like when using the console.
         * This will have implications trough out the whole execution
         * We have to check the instance every time we use ProcessWire, like in our RouteLoader
         */
        parent::initializeContainer();
        if($this->wire instanceof Wire) {
            $this->container->set('processwire', $this->wire);
        } else {
            $this->container->set('processwire', new ProcessWireMock());
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

    /**
     *
     * set Cache and Log Dir based on /site dir
     * @return string
     *
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir().'/assets/symprowire/log';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/assets/symprowire/cache/'.$this->environment;
    }

}
