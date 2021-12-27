<?php

namespace Symprowire\Engine;

use Exception;
use ProcessWire\ProcessWire;
use Symfony\Component\HttpFoundation\Request;
use Symprowire\Exception\SymprowireRequestFactoryException;

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
     * @throws SymprowireRequestFactoryException
     */
    public static function createSympro(ProcessWire $wire): Request
    {
        try {

            $templateName = $wire->page->template->name;
            $path = '/'.$wire->sanitizer->pageName($templateName);
            $action = $wire->input->get('_sympro', 'camelCase') ?: 'index';
            $sympro = $wire->input->get('_sympro', 'camelCase');

            if($sympro) {
                $path = $path . '/' . $sympro;
            }
            $requestAttributes = [
                '_received' => hrtime(true),
                '_processed' => null,
                '_wire' => $wire,
                '_template' => $wire->page->template->name,
                '_path' => $path,
                '_action' => $action,
            ];
            foreach($wire->input->get() as $key => $value) {
                $requestAttributes[$key] = $value;
            }
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


        } catch (Exception $exception) {
            throw new SymprowireRequestFactoryException('Request Creation Failed', 100, $exception);
        }
        return $request;
    }

}
