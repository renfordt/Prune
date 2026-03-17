<?php

use PHPUnit\Framework\TestCase;

/**
 * DummyTest
 *
 * This class contains unit tests for the Dummy class.
 * The `foo` method is expected to return the string 'bar'.
 */
class DummyTest extends TestCase
{
    /**
     * Test that the `foo` method returns 'bar'.
     */
    public function testFooReturnsBar()
    {
        $dummy = new Dummy();
        $this->assertEquals('bar', $dummy->foo());
    }
}