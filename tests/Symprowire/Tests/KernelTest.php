<?php

namespace Symprowire\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symprowire\Engine\KernelTestCase;
use Symprowire\Engine\SymprowireRuntime;
use Symprowire\Exception\SymprowireRequestFactoryException;
use Symprowire\Kernel;

/**
 *
 * @testdox Running Kernel Tests
 *
 */
class KernelTest extends KernelTestCase
{

    /**
     *
     * @testdox Kernel is bootable
     *
     * @covers \Symprowire\Kernel
     *
     */
    public function testBootKernel(): void
    {
        $kernel = self::bootKernel();
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame('test', $kernel->getEnvironment());
    }

    /**
     * @testdox Kernel is runnable and TestController responded
     * @throws SymprowireRequestFactoryException
     *
     * @covers \Symprowire\Kernel
     * @covers \Symprowire\Engine\SymprowireRequest
     * @covers \Symprowire\Engine\SymprowireKernelRunner
     * @covers \Symprowire\Engine\SymprowireRuntime
     */
    public function testRuntimeAndResponse(): void
    {

        $kernel = function () {
            return self::bootKernel();
        };
        $runtime = new SymprowireRuntime(['disable_dotenv' => true]);
        [$kernel, $args] = $runtime->getResolver($kernel)->resolve();
        $symprowire = $kernel(...$args);
        $this->assertSame(0, $runtime->getRunner($symprowire)->run());

        $symprowire = $runtime->getExecutedRunner()->getKernel();
        $this->assertInstanceOf(Kernel::class, $symprowire);
        $this->assertSame('test', $symprowire->getEnvironment());

        $response = $symprowire->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('controller responded', $response->getContent());
        // The Runtime registers a new Error Handler, to get rid of warnings we restore the error handler
        restore_error_handler ();
    }

}
