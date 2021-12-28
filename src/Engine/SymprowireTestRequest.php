<?php

namespace Engine;

use Symfony\Component\HttpFoundation\Request;

class SymprowireTestRequest extends Request
{
    /**
     * We create a TestRequest which will simulate a Request to the _test Route which is annotated in our TestController...
     *
     * @return Request
     */
    public static function createTestSympro(): Request {
        $requestAttributes = [
            '_received' => hrtime(true),
            '_processed' => null,
            '_test' => true,
        ];

        $request = self::create('/_test');

        foreach($requestAttributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $request;
    }
}
