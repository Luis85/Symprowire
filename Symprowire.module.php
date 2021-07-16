<?php namespace ProcessWire;


use App\Installer;

class Symprowire extends Wire implements Module {

    public static function getModuleInfo(): array
    {
        return [
            'title' => 'Symprowire - MVC Framwork for ProcessWire',
            'description' => 'A module integrating Symfony 5.3 with Twig to use Processwire in a MVC approach.',
            'version' => 60,
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
        // change the Script Filename to keep the request inside our module
        $_SERVER['SCRIPT_FILENAME'] = __DIR__.'/public/index.php';
        require_once __DIR__.'/vendor/autoload_runtime.php';
    }

    /**
     * @throws WireException
     */
    public function ___install() {

        /* Create a new frontcontroller Fieldgroup and Template */
        $fg = new Fieldgroup();
        $fg->name = 'frontcontroller';
        $fg->add($this->fields->get('title'));
        $fg->save();

        $t = new Template();
        $t->name = 'frontcontroller';
        $t->fieldgroup = $fg;
        $t->useRoles = true;
        $t->allowPageNum = true;
        $t->urlSegments(true);
        $t->setRoles(['guest'], 'view');
        $t->setIcon('fa-microchip');
        $t->slashUrls = 0;
        $t->slashPageNum = -1;
        $t->slashUrlSegments = -1;
        $t->save();
        $this->message('Added Template frontcontroller');

        /* assign the frontcontroller to Home */
        $home = $this->wire('pages')->get(1);
        $home->setAndSave('template', $t);
        $this->message('Changed Home->template to frontcontroller');

        /* create our file structure */
        $files = $this->wire('files');
        $path = $this->wire('config')->paths;

        $folders = ['templates/twig/'];
        foreach($folders as $folder) {
            $files->mkdir($path->site.$folder);
            $this->message('created Folder: '. $path->site.$folder);
        }
        $this->message('Folder Structure created');

        /* put a dummy template file into our templates folder to let us decide if we want to serve the request trough symprowire */
        file_put_contents($path->templates."frontcontroller.php", "<?php namespace ProcessWire;");
        $this->message('Template View File added');

        $this->message('Calling custom installer');
        $installer = new Installer();
        $installer->run();
        $this->message('Custom installer executed');

    }

    /**
     * @throws WireException
     */
    public function ___uninstall() {

        /* reset the frontcontroller template on home */
        $homeTemplate = $this->wire('templates')->get('home');
        $home = $this->wire('pages')->get(1);
        $home->setAndSave('template', $homeTemplate);

        /* remove the frontcontroller template */
        $frontcontroller = $this->templates->get('frontcontroller');
        if($frontcontroller->id) {
            $this->wire('templates')->delete($frontcontroller);
            $fg = $this->wire('fieldgroups')->get('frontcontroller');
            $this->wire('fieldgroups')->delete($fg);
        }
        // we do not remove any folders created by symprowire, to not delete any user created files
        $this->message('Symprowire Module removed from Database. Folder structure remains intact.');
    }

}
