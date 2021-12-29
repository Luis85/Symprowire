<?php

namespace Symprowire\Tests;

use PHPUnit\Framework\TestCase;
use Symprowire\Exception\SymprowireExecutionException;
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
    public function testSymprowire(): void
    {
        $symprowire = new Symprowire();
        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());
    }

    /**
     *
     * @testdox renderer throws FrameworkException if not executed
     *
     * @covers \Symprowire\Symprowire
     *
     */
    public function testNotExecutedRenderer(): void
    {
        $symprowire = new Symprowire();
        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());
        $this->assertFalse($symprowire->isExecuted());

        $this->expectException(SymprowireFrameworkException::class);
        $symprowire->render();
    }

    /**
     *
     * @testdox is executable without ProcessWire
     *
     * @covers \Symprowire\Symprowire
     *
     * @throws SymprowireExecutionException
     */
    public function testExecution(): void
    {
        $symprowire = new Symprowire();
        $this->assertInstanceOf(SymprowireInterface::class, $symprowire);
        $this->assertTrue($symprowire->isReady());
        $this->assertFalse($symprowire->isExecuted());

        $kernel = $symprowire->execute();
        $this->assertInstanceOf(SymprowireKernelInterface::class, $kernel);

        // The Runtime registers a new Error Handler, to get rid of warnings we restore the error handler
        restore_error_handler ();
    }
}
