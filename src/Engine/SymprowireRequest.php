<?php

namespace Symprowire\Engine;

use ProcessWire\ProcessWire;
use ProcessWire\WireException;
use Symfony\Component\HttpFoundation\Request;

/**
 * The SymprowireRequest Class
 *
 * Take the Request, add Framework Specific Attributes und Return it
 */
class SymprowireRequest extends Request
{

    /**
     *
     * We need the current ProcessWire instance to create our own SymprowireRequest.
     * We add some ProcessWire specific attributes to the Request to make them easy accessable troughout the app
     *
     * @throws WireException
     */
    public static function createSympro(ProcessWire $wire = null, bool $test = false): Request
    {
        /**
         * We cant enter the application without a proper Request which contains ProcessWire
         * The Kernel depends heavily on ProcessWire therefor we have to return a TestRequest if ProcessWire is not set or test is true, which will get routed to our TestController
         */
        if($test || !$wire) return SymprowireTestRequest::createTestSympro();

        $templateName = $wire->page->template->name;
        $path = '/'.$wire->sanitizer->pageName($templateName);
        $pwPath = $wire->page->path;

        /**
         * One way to define a Template Route is to use a _sympro=$action GET Parameter at your URL
         * The Routeloader will add a new route dynamically which will resolve to your Template Controller or to an Exception if no Controller is found
         */
        $action = $wire->input->get('_sympro', 'camelCase') ?: 'index';
        $sympro = $wire->input->get('_sympro', 'camelCase');
        if($sympro) {
            $path = $path . '/' . $sympro;
        }

        /**
         * attach ProcessWire to the Request and add some more details
         */
        $requestAttributes = [
            '_received' => hrtime(true),
            '_processed' => null,
            '_wire' => $wire,
            '_template' => $wire->page->template->name,
            '_path' => $path,
            '_pw_path' => $pwPath,
            '_action' => $action,
        ];
        foreach($wire->input->get() as $key => $value) {
            $requestAttributes[$key] = $value;
        }

        /**
         * to make our Router work we have to set our Template as URI
         * The Controller Resolver will then resolve to our TemplateController
         */
        $serverVars = [];
        $request = self::create($path);
        foreach($_SERVER as $key => $value) {
            if($key === 'REQUEST_URI') {
                $serverVars['REQUEST_URI'] = $path;
            } else {
                $serverVars[$key] = $value;
            }
        }

        $request->initialize($_GET, $_POST, $requestAttributes, $_COOKIE, $_FILES, $serverVars);

        return $request;
    }

}
