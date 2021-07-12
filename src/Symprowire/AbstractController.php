<?php


namespace App\Symprowire;


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

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
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

    public function __construct() {

        $this->input = wire('input');
        $this->session = wire('session');
        $this->page = wire('page');
        $this->pages = wire('pages');
        $this->modules = wire('modules');

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
