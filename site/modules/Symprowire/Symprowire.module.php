<?php namespace ProcessWire;


use Symfony\Component\HttpFoundation\Request;

class Symprowire extends Wire implements Module {

    public static function getModuleInfo(): array
    {
        return [
            'title' => 'Symprowire - A Processwire Request/Response extension',
            'description' => 'A base module integrating Symfony http-foundation and Twig to use Processwire in a MVC approach.',
            'version' => 1,
            'summary' => 'Symprowire - Base Framework Module.',
            'href' => 'https://github.com/Luis85/symprowire',
            'singular' => true,
            'autoload' => 'template=frontcontroller',
            'icon' => 'flask',
            'requires' => [
                'PHP>=7.4',
                'ProcessWire>=3.0.181',
            ],
        ];
    }

    /**
     * @throws WireException
     */
    public function wired() {
        $this->wire('symprowire', $this);
        parent::wired();
    }

    public function init() {
        $this->addHookBefore('TemplateFile::render', function($event) {
            $event->replace = true;
            $this->executeSymprowire();
        });
    }

    /**
     * @throws WireException
     */
    protected function executeSymprowire() {
        $paths = wire('config')->paths;

        require_once $paths->root.'vendor/autoload.php';

        $container = include __DIR__.'/lib/container.php';
        $routes = include __DIR__.'/lib/app.php';

        $container->setParameter('debug', $this->wire('config')->debug);
        $container->setParameter('routes', $routes);

        $request = Request::createFromGlobals();
        $response = $container->get('framework')->handle($request);

        $response->send();
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
        $this->message('Created Template frontcontroller');

        /* assign the frontcontroller to Home */
        $home = $this->wire('pages')->get(1);
        $home->setAndSave('template', $t);
        $this->message('Changed Home->template to frontcontroller');

        /* create our file structure */
        $files = $this->wire('files');
        $path = $this->wire('config')->paths->site;

        $folders = ['src/Controller/', 'src/Entity/', 'src/Service/', 'src/Repository/', 'src/Interface/', 'src/Trait/', 'cache', 'templates/twig/'];
        foreach($folders as $folder) {
            $files->mkdir($path.$folder);
            $this->message('created Folder: '. $path.$folder);
        }
        $this->message('Folder Structure created');

        /* put a dummy template file into our templates folder to let us decide if we want to serve the request trough symprowire */
        file_put_contents($path->templates."frontcontroller.php", "<?php namespace ProcessWire;");
        $this->message('Template View File added');
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
        $this->message('Symprowire Module removed. Folder structure remains intact.');
    }

}
