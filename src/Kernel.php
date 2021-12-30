<?php

namespace Symprowire;

use Exception;
use ProcessWire\ProcessWire;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader as ContainerPhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symprowire\Engine\ProcessWireMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symprowire\Exception\SymprowireVendorConfigurationException;
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
     * Running the Kernel with a Runtime will give us a testable Interface
     * TODO to make the whole setup testable we have to make a ProcessWire Mock, otherwise every test against the business logic depends on the database
     *
     * @param ProcessWire|null $wire
     *
     */
    public function __construct(ProcessWire $wire = null, array $params = ['test' => false])
    {
        if($wire) {
            $this->wire = $wire;
            $debug = $wire->config->debug;
        } else {
            $this->wire = null;
            $debug = true;
        }
        $environment =  $debug ? 'dev' : 'prod';
        $environment = $params['test'] ? 'test' : $environment;
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
     * TODO fix this...
     *
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
            return $this->wire->config->paths->root . 'site/assets/symprowire/' . $this->environment . '/log';
        } else {
            return $this->getProjectDir() . '/var/log/' . $this->environment;
        }
    }

    public function getCacheDir(): string
    {
        if($this->wire instanceof ProcessWire) {
            return $this->wire->config->paths->root . 'site/assets/symprowire/' . $this->environment . '/cache';
        } else {
            return $this->getProjectDir() . '/var/cache/' . $this->environment;
        }
    }

    /**
     * MicrokernelTrait
     *
     * Configures the container after initializing.
     *
     */

    private function getConfigDirAsVendor(): string {
        return $this->wire->config->paths->site . 'vendor/symprowire/symprowire/config';
    }

    /**
     * As we init ProcessWire as a Synthetic Service we have to inject on init and not on configuration. Keep this in mind.
     *
     * @param ContainerConfigurator $container
     * @param LoaderInterface $loader
     * @param ContainerBuilder $builder
     */
    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $configDir = $this->getConfigDir();

        $container->import($configDir.'/{packages}/*.yaml');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.yaml');
        $container->import($configDir.'/{services}.php');

        if (is_file($configDir.'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        }

        if($this->wire instanceof ProcessWire) {
            $vendorConfigDir = $this->getConfigDirAsVendor();
            $container->import($vendorConfigDir.'/{packages}/*.yaml');
            $container->import($vendorConfigDir.'/{packages}/'.$this->environment.'/*.yaml');
            $container->import($vendorConfigDir.'/{services}.php');
            if (is_file($vendorConfigDir.'/services.yaml')) {
                $container->import($vendorConfigDir.'/services.yaml');
                $container->import($vendorConfigDir.'/{services}_'.$this->environment.'.yaml');
            }
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
     */
    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getConfigDir();

        $routes->import($configDir.'/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($configDir.'/{routes}/*.yaml');
        $routes->import($configDir.'/{routes}.php');

        if (is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        }
        if($this->wire instanceof ProcessWire) {
            $vendorConfigDir = $this->getConfigDirAsVendor();

            $routes->import($vendorConfigDir.'/{routes}/'.$this->environment.'/*.yaml');
            $routes->import($vendorConfigDir.'/{routes}/*.yaml');
            $routes->import($vendorConfigDir.'/{routes}.php');

            if (is_file($vendorConfigDir.'/routes.yaml')) {
                $routes->import($vendorConfigDir.'/routes.yaml');
            }
        }
    }

    /**
     * Gets the path to the configuration directory.
     */
    private function getConfigDir(): string
    {
        if($this->wire instanceof ProcessWire) {
            return $this->wire->config->paths->root . 'site/config/';
        } else {
            return $this->getProjectDir().'/config';
        }

    }

    /**
     * Gets the path to the bundles configuration file.
     */
    private function getBundlesPath(): string
    {
        return $this->getConfigDir().'/bundles.php';
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        $contents = require $this->getBundlesPath();
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $kernelClass = false !== strpos(static::class, "@anonymous\0") ? parent::class : static::class;

            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', $kernelClass)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true)
                ;
            }

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');

            $container->addObjectResource($this);
            $container->fileExists($this->getBundlesPath());

            $configureContainer = new \ReflectionMethod($this, 'configureContainer');
            $configuratorClass = $configureContainer->getNumberOfParameters() > 0 && ($type = $configureContainer->getParameters()[0]->getType()) instanceof \ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            if ($configuratorClass && !is_a(ContainerConfigurator::class, $configuratorClass, true)) {
                $configureContainer->getClosure($this)($container, $loader);

                return;
            }

            $file = (new \ReflectionObject($this))->getFileName();
            /* @var ContainerPhpFileLoader $kernelLoader */
            $kernelLoader = $loader->getResolver()->resolve($file);
            $kernelLoader->setCurrentDir(\dirname($file));
            $instanceof = &\Closure::bind(function &() { return $this->instanceof; }, $kernelLoader, $kernelLoader)();

            $valuePreProcessor = AbstractConfigurator::$valuePreProcessor;
            AbstractConfigurator::$valuePreProcessor = function ($value) {
                return $this === $value ? new Reference('kernel') : $value;
            };

            try {
                $configureContainer->getClosure($this)(new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file, $this->getEnvironment()), $loader, $container);
            } finally {
                $instanceof = [];
                $kernelLoader->registerAliasesForSinglyImplementedInterfaces();
                AbstractConfigurator::$valuePreProcessor = $valuePreProcessor;
            }

            $container->setAlias($kernelClass, 'kernel')->setPublic(true);
        });
    }

    /**
     * @throws \ReflectionException
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));
        $collection = new RouteCollection();

        $configureRoutes = new \ReflectionMethod($this, 'configureRoutes');
        $configureRoutes->getClosure($this)(new RoutingConfigurator($collection, $kernelLoader, $file, $file, $this->getEnvironment()));

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            }
        }

        return $collection;
    }

}
