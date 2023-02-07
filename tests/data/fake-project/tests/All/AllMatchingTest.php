<?php

declare(strict_types=1);

namespace Fake\Test\All;

use PHPUnit\Framework\TestCase;

class AllMatchingTest extends TestCase
{
    /**
     * @test
     */
    public function fake(): void
    {
    }

    public function testFake(): void
    {
    }

    public function notATest(): void
    {
    }
}
