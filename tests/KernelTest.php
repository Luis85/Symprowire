<?php

namespace Symprowire\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symprowire\Engine\KernelTestCase;
use Symprowire\Engine\SymprowireRuntime;
use Symprowire\Exception\SymprowireRequestFactoryException;
use Symprowire\Exception\SymprowireRuntimeException;
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
     * @testdox is bootable
     *
     * @covers \Symprowire\Kernel
     *
     */
    public function testBootKernel(): Kernel
    {
        $kernel = self::bootKernel();
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame('test', $kernel->getEnvironment());

        return $kernel;
    }

    /**
     * @testdox is runnable and has valid Response from TestController
     * @throws SymprowireRequestFactoryException
     * @throws SymprowireRuntimeException
     *
     * @depends testBootKernel
     *
     * @covers \Symprowire\Kernel
     * @covers \Symprowire\Engine\SymprowireRequest
     * @covers \Symprowire\Engine\SymprowireKernelRunner
     * @covers \Symprowire\Engine\SymprowireRuntime
     */
    public function testRuntimeAndResponse($kernel): void
    {
        // Remember, the Runtime expects a callable Kernel and NOT the actual Kernel instance
        $kernel = function () use ($kernel) {
            return $kernel;
        };

        $runtime = new SymprowireRuntime();
        [$kernel, $args] = $runtime->getResolver($kernel)->resolve();
        $symprowire = $kernel(...$args);
        $this->assertSame(0, $runtime->getRunner($symprowire)->run());

        $symprowire = $runtime->getExecutedRunner()->getKernel();
        $this->assertInstanceOf(Kernel::class, $symprowire);
        $this->assertSame('test', $symprowire->getEnvironment());

        $response = $symprowire->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsInt($symprowire->getRequest()->attributes->get('_processed'));
        $this->assertSame('controller.responded', $response->getContent());
        // The Runtime registers a new Error Handler, to get rid of warnings we restore the error handler
        restore_error_handler ();
    }

}
