<?php

use PHPUnit\Framework\TestCase;

final class StandardRouterTest extends TestCase
{

    /**
     * @covers \Core\Router\StandardRouter::__construct
     */
    public function testConstruct(): void
    {
        $cache = $this->getMockBuilder(\Core\Cache\Volatile\Apcu::class)
            ->setMethods(['entry'])
            ->getMock();

        $defaultRoute = '/test';
        $routingTable = [];
        $abstractRouter = new \Core\Router\StandardRouter($routingTable, $defaultRoute, $cache);
        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }

}