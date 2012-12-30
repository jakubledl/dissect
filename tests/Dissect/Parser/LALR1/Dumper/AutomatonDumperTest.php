<?php

namespace Dissect\Parser\LALR1\Dumper;

use Dissect\Parser\LALR1\Analysis\Analyzer;
use PHPUnit_Framework_TestCase;

class AutomatonDumperTest extends PHPUnit_Framework_TestCase
{
    protected $dumper;

    protected function setUp()
    {
        $analyzer = new Analyzer();
        $automaton = $analyzer->analyze(new ExampleGrammar())->getAutomaton();
        $this->dumper = new AutomatonDumper($automaton);
    }

    /**
     * @test
     */
    public function dumpDumpsTheEntireAutomaton()
    {
        $this->assertStringEqualsFile(
            __DIR__ . '/res/graphviz/automaton.dot',
            $this->dumper->dump()
        );
    }

    /**
     * @test
     */
    public function dumpStateDumpsOnlyTheSpecifiedStateAndTransitions()
    {
        $this->assertStringEqualsFile(
            __DIR__ . '/res/graphviz/state.dot',
            $this->dumper->dumpState(2)
        );
    }
}
