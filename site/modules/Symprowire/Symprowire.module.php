<?php namespace ProcessWire;

class Symprowire extends Wire implements Module {

    public static function getModuleInfo(): array
    {
        return [
            'title' => 'Symprowire',
            'description' => 'Base Methods and Symfony HTTP Foundation init',
            'version' => 1,
            'summary' => 'Base Framework Module.',
            'href' => 'https://github.com/Luis85/symprowire',
            'singular' => true,
            'autoload' => 'template=frontcontroller',
            'icon' => 'flask',
            'requires' => [
                'PHP>=7.4',
                'ProcessWire>=3.0.178',
            ],
        ];
    }

    public function wired() {
        $this->wire('symprowire', $this);
        parent::wired();
    }

    public function init() { }

    /**
     * @throws WireException
     */
    public function ___install() {
        $fg = new Fieldgroup();
        $fg->name = 'frontcontroller';
        $fg->add($this->fields->get('title'));
        $fg->save();

        $t = new Template();
        $t->name = 'frontcontroller';
        $t->fieldgroup = $fg;
        $t->useRoles = true;
        $t->https = true;
        $t->allowPageNum = true;
        $t->urlSegments(true);
        $t->setRoles(['guest'], 'view');
        $t->setIcon('fa-microchip');
        $t->slashUrls = false;
        $t->slashPageNum = false;
        $t->slashUrlSegments = false;
        $t->save();

        $home = $this->wire('pages')->get(1);
        $home->setAndSave('template', $t);
    }

    /**
     * @throws WireException
     */
    public function ___uninstall() {

        $homeTemplate = $this->wire('templates')->get('home');
        $home = $this->wire('pages')->get(1);
        $home->setAndSave('template', $homeTemplate);

        $frontcontroller = $this->templates->get('frontcontroller');
        if($frontcontroller->id) {
            $this->wire('templates')->delete($frontcontroller);
            $fg = $this->wire('fieldgroups')->get('frontcontroller');
            $this->wire('fieldgroups')->delete($fg);
        }
    }


}
