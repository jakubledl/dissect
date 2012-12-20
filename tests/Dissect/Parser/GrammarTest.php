<?php

namespace Dissect\Parser;

use PHPUnit_Framework_TestCase;

class GrammarTest extends PHPUnit_Framework_TestCase
{
    protected $grammar;

    protected function setUp()
    {
        $this->grammar = new ExampleGrammar();
    }

    /**
     * @test
     */
    public function ruleAlternativesShouldHaveTheSameName()
    {
        $rules = $this->grammar->getRules();

        $this->assertEquals('Foo', $rules[1]->getName());
        $this->assertEquals('Foo', $rules[2]->getName());
    }

    /**
     * @test
     */
    public function theGrammarShouldBeAugmentedWithAStartRule()
    {
        $this->assertEquals(
            Grammar::START_RULE_NAME,
            $this->grammar->getStartRule()->getName()
        );

        $this->assertEquals(
            array('Foo'),
            $this->grammar->getStartRule()->getComponents()
        );
    }

    /**
     * @test
     */
    public function shouldReturnAlternativesGroupedByName()
    {
        $rules = $this->grammar->getGroupedRules();
        $this->assertCount(2, $rules['Foo']);
    }
}
