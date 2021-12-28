<?php

namespace Symprowire\Tests;

use Symprowire\Engine\KernelTestCase;

/**
 *
 * @testdox Running Kernel Tests
 * @covers \Symprowire\Kernel
 */
class KernelBootTest extends KernelTestCase
{
    /**
     *
     * @testdox Kernel booted
     */
    public function testKernelBoot(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
    }
}
