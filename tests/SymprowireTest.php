<?php

namespace Symprowire\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symprowire\Exception\SymprowireFrameworkException;
use Symprowire\Interfaces\SymprowireInterface;
use Symprowire\Interfaces\SymprowireKernelInterface;
use Symprowire\Symprowire;

/**
 *
 * @testdox Running Symprowire Tests
 *
 * @covers \Symprowire\Symprowire
 *
 */
class SymprowireTest extends TestCase
{
    /**
     *
     *
     * @testdox is spawnable
     *
     * @covers \Symprowire\Symprowire
     *
     */
    public function testSymprowireSpawn(): Symprowire
    {
        $symprowire = new Symprowire(['test' => true]);
        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());
        return $symprowire;
    }

    /**
     *
     * @testdox renderer throws FrameworkException if not executed
     *
     * @depends testSymprowireSpawn
     * @covers \Symprowire\Symprowire
     *
     */
    public function testNotExecutedRenderer(Symprowire $symprowire): Symprowire
    {
        $this->assertTrue($symprowire->isReady());
        $this->assertFalse($symprowire->isExecuted());

        $this->expectException(SymprowireFrameworkException::class);
        $symprowire->render();

        return $symprowire;
    }

    /**
     *
     * @testdox is executable without ProcessWire
     *
     * @covers \Symprowire\Symprowire
     */
    public function testExecution(): SymprowireKernelInterface
    {
        $symprowire = new Symprowire(['test' => true]);
        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());

        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());
        $this->assertFalse($symprowire->isExecuted());

        $kernel = $symprowire->execute();
        $this->assertInstanceOf(SymprowireKernelInterface::class, $kernel);
        $this->assertTrue($symprowire->isExecuted());

        $request = $kernel->getRequest();
        $this->assertIsInt($request->attributes->get('_processed'));
        $this->assertInstanceOf(Request::class, $request);

        // The Runtime registers a new Error Handler, to get rid of warnings we restore the error handler
        restore_error_handler ();

        return $kernel;
    }

    /**
     *
     * @testdox executed Kernel has valid Response from TestController
     *
     * @depends testExecution
     * @covers \Symprowire\Symprowire
     */
    public function testExecutionHasResponse($kernel): void {

        $response = $kernel->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('controller.responded', $response->getContent());

    }
}
