<?php

namespace Symprowire\Tests;

use PHPUnit\Framework\TestCase;
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
     * TODO make this stuff usefull...
     *
     * @testdox is spawnable
     *
     * @covers \Symprowire\Symprowire
     *
     */
    public function testSymprowire(): void
    {
        $symprowire = new Symprowire();
        $this->assertIsObject($symprowire);
    }
}
