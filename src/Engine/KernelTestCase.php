<?php

namespace Symprowire\Engine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as KernelTest;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelTestCase extends KernelTest
{
    /**
     * Boots the Kernel for this test.
     */
    protected static function bootKernel(array $options = []): KernelInterface
    {
        static::ensureKernelShutdown();

        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }
        static::$kernel = new static::$class(null, true);
        static::$kernel->boot();
        static::$booted = true;

        return static::$kernel;
    }
}
