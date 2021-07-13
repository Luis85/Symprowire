<?php


namespace Symprowire\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;

use Symprowire\Interfaces\AbstractControllerInterface;
use Symprowire\Repository\ModulesRepository;
use Symprowire\Repository\PagesRepository;

use ProcessWire\Wire;
use ProcessWire\Config;
use ProcessWire\Fields;
use ProcessWire\Fieldtypes;
use ProcessWire\Modules;
use ProcessWire\Notices;
use ProcessWire\Page;
use ProcessWire\Pages;
use ProcessWire\Permissions;
use ProcessWire\ProcessWire;
use ProcessWire\Roles;
use ProcessWire\Sanitizer;
use ProcessWire\Session;
use ProcessWire\Templates;
use ProcessWire\User;
use ProcessWire\Users;
use ProcessWire\WireDatabasePDO;
use ProcessWire\WireDateTime;
use ProcessWire\WireFileTools;
use ProcessWire\WireHooks;
use ProcessWire\WireInput;
use ProcessWire\WireMailTools;

use function ProcessWire\wire;

abstract class AbstractController extends SymfonyController implements AbstractControllerInterface
{
    /**
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected $input;
    /**
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected $session;
    /**
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected $pages;
    /**
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected $modules;
    /**
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected $page;

    protected $user;

    public function __construct(ModulesRepository $modulesRepository, PagesRepository $pagesRepository) {

        $this->input = wire('input');
        $this->session = wire('session');
        $this->page = wire('page');
        $this->pages = $pagesRepository;
        $this->modules = $modulesRepository;
        $this->user = wire('user');

    }

    /**
     *
     * @return mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     * @var mixed|Config|Fields|Fieldtypes|Modules|Notices|Page|Pages|Permissions|ProcessWire|Roles|Sanitizer|Session|Templates|User|Users|Wire|WireDatabasePDO|WireDateTime|WireFileTools|WireHooks|WireInput|WireMailTools|string|null
     */
    protected function wire(string $name) {
        return wire($name);
    }
}
