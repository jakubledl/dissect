<?php

namespace Dissect\Parser;

use PHPUnit_Framework_TestCase;

class RuleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getComponentShouldReturnNullIfAskedForComponentOutOfRange()
    {
        $r = new Rule(1, 'Foo', array('x', 'y'));
        $this->assertEquals('y', $r->getComponent(1));
        $this->assertNull($r->getComponent(2));
    }
}
