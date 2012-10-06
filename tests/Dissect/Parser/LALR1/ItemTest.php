<?php

namespace Dissect\Parser\LALR1;

use Dissect\Parser\Rule;
use PHPUnit_Framework_TestCase;

class ItemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getComponentAtDotIndexShouldReturnTheComponentAboutToBeEncountered()
    {
        $item = new Item(new Rule(1, 'A', array('a', 'b', 'c')), 1);

        $this->assertEquals('b', $item->getComponentAtDotIndex());
    }

    /**
     * @test
     */
    public function itemShouldBeAReductionItemIfAllComponentsHaveBeenEncountered()
    {
        $item = new Item(new Rule(1, 'A', array('a', 'b', 'c')), 1);
        $this->assertFalse($item->isReductionItem());

        $item = new Item(new Rule(1, 'A', array('a', 'b', 'c')), 3);
        $this->assertTrue($item->isReductionItem());
    }
}
