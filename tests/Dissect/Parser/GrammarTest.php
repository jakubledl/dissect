<?php

namespace Dissect\Parser;

use PHPUnit_Framework_TestCase;

class GrammarTest extends PHPUnit_Framework_TestCase
{
    protected $grammar;

    protected function setUp()
    {
        $this->grammar = new Grammar();
    }

    /**
     * @test
     */
    public function addRuleShouldCreateANewRuleAndNumberItAutomatically()
    {
        $rule = $this->grammar->rule('Foo', array('x', 'y', 'z'));

        $this->assertInstanceOf('Dissect\\Parser\\Rule', $rule);
        $this->assertEquals(1, $rule->getNumber());
        $this->assertEquals(3, count($rule->getComponents()));

        $this->assertEquals(array('Foo'), $this->grammar->getNonterminals());
    }

    /**
     * @test
     */
    public function setStartRuleShouldAugmentTheGrammarWithASpecialRuleAtPosition0()
    {
        $this->grammar->rule('Foo', array('x', 'y', 'z'));
        $this->grammar->start('Foo');

        $rule = $this->grammar->getStartRule();

        $this->assertEquals(0, $rule->getNumber());
        $this->assertEquals(Grammar::START_RULE_NAME, $rule->getName());
        $this->assertEquals(array('Foo'), $rule->getComponents());
    }
}
