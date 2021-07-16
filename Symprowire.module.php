<?php namespace ProcessWire;


class Symprowire extends Wire implements Module {

    public static function getModuleInfo(): array
    {
        return [
            'title' => 'Symprowire - MVC Framwork for ProcessWire',
            'description' => 'A module integrating Symfony 5.3 with Twig to use Processwire in a MVC approach.',
            'version' => 70,
            'summary' => 'Symprowire - Base Framework Module.',
            'href' => 'https://github.com/Luis85/symprowire',
            'singular' => true,
            'autoload' => 'template!=admin',
            'icon' => 'flask',
            'requires' => [
                'PHP>=7.4',
                'ProcessWire>=3.0.181',
            ],
        ];
    }

    public function init()
    {
        // this prevents Symprowire from running if Module Uninstall is called
        if($this->input->get->name === 'Symprowire' && $this->page->name === 'module') return;

        // change the Script Filename to keep the request inside our module
        $_SERVER['SCRIPT_FILENAME'] = __DIR__.'/public/index.php';
        require_once __DIR__ . '/vendor/autoload_runtime.php';
    }

    /**
     * @throws WireException
     */
    public function ___install() {

        /* create our file structure */
        $files = $this->wire('files');
        $path = $this->wire('config')->paths;

        $folders = ['templates/twig/'];
        foreach($folders as $folder) {
            $files->mkdir($path->site.$folder);
            $this->message('created Folder: '. $path->site.$folder);
        }

        $this->message('Calling custom installer');
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Installer.php';
        $installer = new Installer();
        $installer->run();
        $this->message('Custom installer executed');

    }

    /**
     * @throws WireException
     */
    public function ___uninstall() {
        // we do not remove any folders created by Symprowire to not delete any user created files
        $this->message('Module removed from Database. Folder structure remains intact.');
    }

}
