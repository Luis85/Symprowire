<?php

namespace Symprowire\Tests;

use PHPUnit\Framework\TestCase;
use Symprowire\Exception\SymprowireFrameworkException;
use Symprowire\Interfaces\SymprowireInterface;
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
}
