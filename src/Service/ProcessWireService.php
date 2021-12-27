<?php

namespace Symprowire\Service;

use ProcessWire\_Module;
use ProcessWire\Module;
use ProcessWire\ProcessWire;
use ProcessWire\WirePermissionException;
use Symprowire\Exception\ModuleLoadingException;
use Symprowire\Exception\ModuleNotInstalledException;

class ProcessWireService
{
    protected ProcessWire $wire;

    /**
     * @param ProcessWire $processWire
     *
     * Thanks to the Depedency Injection we can request the current ProcessWire Instance as a normal Service
     */
    public function __construct(ProcessWire $processWire) {
        $this->wire = $processWire;
    }

    /**
     * @throws WirePermissionException
     * @throws ModuleNotInstalledException
     * @throws ModuleLoadingException
     *
     * An easy Interface to request a Module from ProcessWire
     * the function will throw if the requested Module is not installed or loading failed
     */
    public function getModule(string $module): _Module|Module|string|null
    {
        $modules = $this->wire->modules;

        if(!$modules->isInstalled($module)) throw new ModuleNotInstalledException($module . ' is not installed', 300);
        if(!$modules->get($module)) throw new ModuleLoadingException($module . ' could not be loaded', 300);

        return $modules->get($module);

    }

    /**
     * @throws WirePermissionException
     * @throws ModuleNotInstalledException
     * @throws ModuleLoadingException
     *
     * Get the TracyDebugger directly as Module
     * just a shortcut which throws the same Exceptions as ::getModule()
     */
    public function getTracy(): _Module|Module|string|null
    {
        return $this->getModule('TracyDebugger');
    }
}
