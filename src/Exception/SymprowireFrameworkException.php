<?php

namespace Symprowire\Exception;

use Exception;
use JetBrains\PhpStorm\Pure;

abstract class SymprowireFrameworkException extends Exception
{

    #[Pure]
    public function __construct(string $message, int $code, Exception $previous = null) {
        $message = $message . ' / ' . $previous ?: $previous->getMessage();
        parent::__construct($message, $code, $previous);
    }
}
