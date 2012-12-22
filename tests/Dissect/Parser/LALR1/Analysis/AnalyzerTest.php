<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;
use Dissect\Parser\Parser;
use PHPUnit_Framework_TestCase;

class AnalyzerTest extends PHPUnit_Framework_TestCase
{
    protected $analyzer = null;

    /**
     * @test
     */
    public function automatonShouldBeCorrectlyBuilt()
    {
        $grammar = new Grammar();

        $grammar('S')
            ->is('a', 'S', 'b')
            ->is();

        $grammar->start('S');

        $result = $this->getAnalysisResult($grammar);
        $table = $result->getAutomaton()->getTransitionTable();

        $this->assertEquals(1, $table[0]['S']);
        $this->assertEquals(2, $table[0]['a']);
        $this->assertEquals(2, $table[2]['a']);
        $this->assertEquals(3, $table[2]['S']);
        $this->assertEquals(4, $table[3]['b']);
    }

    /**
     * @test
     */
    public function lookaheadShouldBeCorrectlyPumped()
    {
        $grammar = new Grammar();

        $grammar('S')
            ->is('A', 'B', 'C', 'D');

        $grammar('A')
            ->is('a');

        $grammar('B')
            ->is('b');

        $grammar('C')
            ->is(/* empty */);

        $grammar('D')
            ->is('d');

        $grammar->start('S');

        $automaton = $this->getAnalysisResult($grammar)->getAutomaton();

        $this->assertEquals(
            array(Parser::EOF_TOKEN_TYPE),
            $automaton->getState(1)->get(0, 1)->getLookahead()
        );

        $this->assertEquals(
            array('b'),
            $automaton->getState(3)->get(2, 1)->getLookahead()
        );

        $this->assertEquals(
            array('d'),
            $automaton->getState(4)->get(4, 0)->getLookahead()
        );

        $this->assertEquals(
            array('d'),
            $automaton->getState(5)->get(3, 1)->getLookahead()
        );

        $this->assertEquals(
            array(Parser::EOF_TOKEN_TYPE),
            $automaton->getState(7)->get(1, 4)->getLookahead()
        );

        $this->assertEquals(
            array(Parser::EOF_TOKEN_TYPE),
            $automaton->getState(8)->get(5, 1)->getLookahead()
        );
    }

    protected function getAnalysisResult(Grammar $grammar)
    {
        return $this->getAnalyzer()->analyze($grammar);
    }

    protected function getAnalyzer()
    {
        if ($this->analyzer === null) {
            $this->analyzer = new Analyzer();
        }

        return $this->analyzer;
    }
}
