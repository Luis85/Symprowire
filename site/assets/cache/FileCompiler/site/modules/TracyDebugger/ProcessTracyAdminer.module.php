<?php

class ProcessTracyAdminer extends \ProcessWire\Process implements \ProcessWire\Module, \ProcessWire\ConfigurableModule {
    public static function getModuleInfo() {
        return array(
            'title' => \ProcessWire\__('Process Tracy Adminer', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php'),
            'summary' => \ProcessWire\__('Adminer page for TracyDebugger.', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php'),
            'author' => 'Adrian Jones',
            'href' => 'https://processwire.com/talk/topic/12208-tracy-debugger/',
            'version' => '1.1.3',
            'autoload' => false,
            'singular' => true,
            'requires'  => 'ProcessWire>=2.7.2, PHP>=5.4.4, TracyDebugger',
            'icon' => 'database',
            'page' => array(
                'name' => 'adminer',
                'parent' => 'setup',
                'title' => 'Adminer'
            )
        );
    }


   /**
     * Default configuration for module
     *
     */
    static public function getDefaultData() {
        return array(
            "themeColor" => 'blue',
            "jsonMaxLevel" => 3,
            "jsonInTable" => 1,
            "jsonInEdit" => 1,
            "jsonMaxTextLength" => 200
        );
    }


    /**
     * Populate the default config data
     *
     */
    public function __construct() {
        foreach(self::getDefaultData() as $key => $value) {
            $this->$key = $value;
        }
    }


    public function ___execute() {

        error_reporting(0);
        ini_set('display_errors', 0);

        $_GET['db'] = $this->wire('config')->dbName;

        function adminer_object() {

 require_once(\ProcessWire\wire('files')->compile(\ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/panels/Adminer/plugins/plugin.php',array('includes'=>true,'namespace'=>true,'modules'=>false,'skipIfNamespace'=>false)));

            foreach (glob(\ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger'.'/panels/Adminer/plugins/*.php') as $filename) {
                require_once $filename/*NoCompile*/;
            }

            $data = \ProcessWire\wire('modules')->getModuleConfigData('ProcessTracyAdminer');
            $data = array_merge(\ProcessTracyAdminer::getDefaultData(), $data);

            $port = \ProcessWire\wire('config')->dbPort ? ':' . \ProcessWire\wire('config')->dbPort : '';

            $plugins = [
                new AdminerFrames,
                new AdminerProcessWireLogin(\ProcessWire\wire('config')->urls->admin, \ProcessWire\wire('config')->dbHost . $port, \ProcessWire\wire('config')->dbName, \ProcessWire\wire('config')->dbUser, \ProcessWire\wire('config')->dbPass, \ProcessWire\wire('config')->dbName),
                new AdminerTablesFilter(),
                new AdminerSimpleMenu(),
                new AdminerCollations(),
                new AdminerJsonPreview($data['jsonMaxLevel'], $data['jsonInTable'], $data['jsonInEdit'], $data['jsonMaxTextLength']),
                new AdminerDumpJson,
                new AdminerDumpBz2,
                new AdminerDumpZip,
                new AdminerDumpAlter,
                new AdminerTheme("default-".$data['themeColor'])
            ];

            return new AdminerPlugin($plugins);
        }

        $_GET['username'] = '';
        require_once \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger' . '/panels/Adminer/adminer-4.8.1-mysql.php'/*NoCompile*/;
        exit;
    }

    /**
     * Return an InputfieldWrapper of Inputfields used to configure the class
     *
     * @param array $data Array of config values indexed by field name
     * @return InputfieldsWrapper
     *
     */
    public function getModuleConfigInputfields(array $data) {

        $wrapper = new \ProcessWire\InputfieldWrapper();

        $f = $this->wire('modules')->get("InputfieldSelect");
        $f->attr('name', 'themeColor');
        $f->label = \ProcessWire\__('Theme color', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->addOption('blue', 'Blue');
        $f->addOption('green', 'Green');
        $f->addOption('orange', 'Orange');
        $f->required = true;
        if($this->data['themeColor']) $f->attr('value', $this->data['themeColor']);
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldText");
        $f->attr('name', 'jsonMaxLevel');
        $f->label = \ProcessWire\__('JSON max level', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->description = \ProcessWire\__('Max. level in recursion. 0 means no limit.', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->notes = \ProcessWire\__('Default: 3', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->required = true;
        if($this->data['jsonMaxLevel']) $f->attr('value', $this->data['jsonMaxLevel']);
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldCheckbox");
        $f->attr('name', 'jsonInTable');
        $f->label = \ProcessWire\__('JSON In Table', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->description = \ProcessWire\__('Whether apply JSON preview in selection table.', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->notes = \ProcessWire\__('Default: true', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->attr('checked', $this->data['jsonInTable'] == '1' ? 'checked' : '');
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldCheckbox");
        $f->attr('name', 'jsonInEdit');
        $f->label = \ProcessWire\__('JSON In Edit', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->description = \ProcessWire\__('Whether apply JSON preview in edit form.', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->notes = \ProcessWire\__('Default: true', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->attr('checked', $this->data['jsonInEdit'] == '1' ? 'checked' : '');
        $wrapper->add($f);

        $f = $this->wire('modules')->get("InputfieldText");
        $f->attr('name', 'jsonMaxTextLength');
        $f->label = \ProcessWire\__('JSON max text length', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->description = \ProcessWire\__('Maximal length of string values. Longer texts will be truncated with ellipsis sign. 0 means no limit.', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->notes = \ProcessWire\__('Default: 200', \ProcessWire\wire("config")->paths->root . 'site/modules/TracyDebugger/ProcessTracyAdminer.module.php');
        $f->required = true;
        if($this->data['jsonMaxTextLength']) $f->attr('value', $this->data['jsonMaxTextLength']);
        $wrapper->add($f);

        return $wrapper;

    }

}
