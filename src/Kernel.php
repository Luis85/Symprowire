<?php

namespace Symprowire;

use ProcessWire\ProcessWire;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symprowire\Engine\ProcessWireMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symprowire\Interfaces\SymprowireKernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;


class Kernel extends BaseKernel implements SymprowireKernelInterface
{

    use MicroKernelTrait;

    protected ?Request $request = null;
    protected ?Response $response = null;
    protected string $executionTime = '';
    protected ?int $executionTimeRaw = null;
    protected ?ProcessWire $wire;
    protected array $params;

    /**
     * The Symprowire Kernel
     * --------------------------
     *
     * Based on the Symfony HttpKernel we will get a Request, attach ProcessWire to the Kernel, handle the processing and return a Response to ProcessWire's page->render
     *
     * TODO Kernel Lifecycle Description
     *
     * Construct The Symprowire Kernel
     * --------------------------
     *
     * Running the Kernel with a Runtime will give us a testable Interface
     * TODO to make the whole setup testable we have to make a ProcessWire Mock, otherwise every test against the business logic depends on the database
     * TODO: make $params['library'] a computed method or introduce a new Environment
     *
     * @param ProcessWire|null $wire
     *
     */
    public function __construct(ProcessWire $wire = null, array $params = [])
    {
        $this->params = [
            'environment' => 'prod',
            'debug' => false,
            'test' => false
        ];
        $this->params = array_merge($this->params, $params);
        $this->params['library'] = (bool) $wire;

        if($wire instanceof ProcessWire) {
            $this->wire = $wire;
            $this->params['debug'] = $wire->config->debug;
        } else {
            $this->wire = null;
        }

        $environment = $this->params['test'] ? 'test' : $this->params['environment'];
        $debug = $this->params['debug'];

        // Force Settings
        if(isset($params['environment'])) $environment = $params['environment'];
        if(isset($params['debug'])) $debug = $params['debug'];

        parent::__construct($environment, (bool) $debug);
    }

    /**
     *
     * Initialize the Container
     * Inject ProcessWire in the DI Container if present
     * this will setup our configured synthetic service and make ProcessWire available in the System
     *
     * TODO: In order to use the console we have to fill the synthetic pw service with a mock. This should be refactored I guess
     *
     * TODO: fix this...
     * we use a Mock which extends Wire if ProcessWire is not set on construction. Like when using the console.
     * This will have implications trough out the whole execution
     * We have to check the instance every time we use ProcessWire, like in our RouteLoader
     *
     */
    protected function initializeContainer(): void
    {
        parent::initializeContainer();
        if($this->wire instanceof ProcessWire) {
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
     * @return ProcessWire|null
     */
    public function getProcessWire(): ?ProcessWire {
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
        if($this->wire instanceof ProcessWire) {
            return $this->getProjectDir() . '/assets/symprowire/log/' . $this->environment;
        } else {
            return $this->getProjectDir() . '/var/log/' . $this->environment;
        }
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        if($this->wire instanceof ProcessWire) {
            return $this->getProjectDir() . '/assets/symprowire/cache/' . $this->environment;
        } else {
            return $this->getProjectDir() . '/var/cache/' . $this->environment;
        }
    }

    /**
     * As we init ProcessWire as a Synthetic Service we have to inject on init and not on configuration. Keep this in mind.
     *
     * @param ContainerConfigurator $container
     * @param LoaderInterface $loader
     * @param ContainerBuilder $builder
     *
     */
    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        // we call Vendor Config first to could override it in ProcessWire later
        if($this->params['library']) {
            $vendorConfigDir = $this->getConfigDirAsVendor();
            $container->import($vendorConfigDir.'/{packages}/*.yaml');
            $container->import($vendorConfigDir.'/{services}.php');
            if (is_file($vendorConfigDir.'/services.yaml')) {
                $container->import($vendorConfigDir.'/services.yaml');
            }
            if (is_file($vendorConfigDir.'/processwire-services.yaml')) {
                $container->import($vendorConfigDir.'/processwire-services.yaml');
            }
        }

        $configDir = $this->getConfigDir();
        $container->import($configDir.'/{packages}/*.yaml');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.yaml');
        $container->import($configDir.'/{services}.php');

        if (is_file($configDir.'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        }
    }

    /**
     * Adds or imports routes into your application.
     *
     *     $routes->import($this->getConfigDir().'/*.{yaml,php}');
     *     $routes
     *         ->add('admin_dashboard', '/admin')
     *         ->controller('App\Controller\AdminController::dashboard')
     *     ;
     *
     */
    private function configureRoutes(RoutingConfigurator $routes): void
    {
        // we call Vendor Config first to could override it in ProcessWire later
        if($this->params['library']) {
            $vendorConfigDir = $this->getConfigDirAsVendor();

            $routes->import($vendorConfigDir.'/{routes}/'.$this->environment.'/*.yaml');
            $routes->import($vendorConfigDir.'/{routes}/*.yaml');
            $routes->import($vendorConfigDir.'/{routes}.php');

            if (is_file($vendorConfigDir.'/routes.yaml')) {
                $routes->import($vendorConfigDir.'/routes.yaml');
            }
            if (is_file($vendorConfigDir.'/processwire-routes.yaml')) {
                $routes->import($vendorConfigDir.'/processwire-routes.yaml');
            }
        }

        $configDir = $this->getConfigDir();
        $routes->import($configDir.'/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($configDir.'/{routes}/*.yaml');
        $routes->import($configDir.'/{routes}.php');

        if (is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        }
    }

    /**
     *
     * Gets the path to the configuration if Symprowire is used as ProcessWire renderer.
     *
     */
    private function getConfigDirAsVendor(): string {
        return $this->wire->config->paths->site . 'vendor/symprowire/symprowire/config';
    }

    /**
     * Gets the path to the bundles configuration file.
     *
     */
    private function getBundlesPath(): string
    {
        if($this->params['library']) {
            return $this->getConfigDirAsVendor().'/bundles-library.php';
        } else {
            return $this->getConfigDir().'/bundles.php';
        }

    }

    /**
     * Gets the application root dir. Would be path/to/site in a ProcessWire Environment and the package.json root in standalone mode
     */
    public function getProjectDir(): string
    {
        if (!isset($this->projectDir)) {
            $r = new \ReflectionObject($this);

            if (!is_file($dir = $r->getFileName())) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!is_file($dir.'/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $this->wire instanceof ProcessWire ? $this->wire->config->paths->root . 'site' : $dir ;
        }

        return $this->projectDir;
    }

}
