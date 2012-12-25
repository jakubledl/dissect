<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\Analysis\Exception\ReduceReduceConflictException;
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

    /**
     * @test
     */
    public function parseTableShouldBeCorrectlyBuilt()
    {
        $grammar = new Grammar();

        $grammar('S')
            ->is('a', 'S', 'b')
            ->is(/* empty */);

        $grammar->start('S');

        $table = $this->getAnalysisResult($grammar)->getParseTable();

        // shift(2)
        $this->assertEquals(2, $table['action'][0]['a']);

        // reduce(S -> )
        $this->assertEquals(-2, $table['action'][0][Parser::EOF_TOKEN_TYPE]);

        // accept
        $this->assertEquals(0, $table['action'][1][Parser::EOF_TOKEN_TYPE]);

        // shift(2)
        $this->assertEquals(2, $table['action'][2]['a']);

        // reduce(S -> )
        $this->assertEquals(-2, $table['action'][2]['b']);

        // shift(4)
        $this->assertEquals(4, $table['action'][3]['b']);

        // reduce(S -> a S b)
        $this->assertEquals(-1, $table['action'][4]['b']);
        $this->assertEquals(-1, $table['action'][4][Parser::EOF_TOKEN_TYPE]);

        $this->assertEquals(1, $table['goto'][0]['S']);
        $this->assertEquals(3, $table['goto'][2]['S']);
    }

    /**
     * @test
     */
    public function unexpectedConflictsShouldThrowAnException()
    {
        $grammar = new Grammar();

        $grammar('S')
            ->is('a', 'b', 'C', 'd')
            ->is('a', 'b', 'E', 'd');

        $grammar('C')
            ->is(/* empty */);

        $grammar('E')
            ->is(/* empty */);

        $grammar->start('S');

        try {
            $result = $this->getAnalysisResult($grammar);
            $this->fail('Expected an exception warning of a reduce/reduce conflict.');
        } catch(ReduceReduceConflictException $e) {
            $this->assertEquals(3, $e->getStateNumber());
            $this->assertEquals('d', $e->getLookahead());
            $this->assertEquals(3, $e->getFirstRule()->getNumber());
            $this->assertEquals(4, $e->getSecondRule()->getNumber());
        }
    }

    /**
     * @test
     */
    public function expectedConflictsShouldBeRecorded()
    {
        $grammar = new Grammar();

        $grammar('S')
            ->is('S', 'S', 'S')
            ->is('S', 'S')
            ->is('b');

        $grammar->resolve(Grammar::ALL);
        $grammar->start('S');

        $conflicts = $this->getAnalysisResult($grammar)->getResolvedConflicts();

        $this->assertCount(4, $conflicts);

        $conflict = $conflicts[0];

        $this->assertEquals(3, $conflict['state']);
        $this->assertEquals('b', $conflict['lookahead']);
        $this->assertEquals(2, $conflict['rule']->getNumber());
        $this->assertEquals(Grammar::SHIFT, $conflict['resolution']);

        $conflict = $conflicts[1];

        $this->assertEquals(4, $conflict['state']);
        $this->assertEquals('b', $conflict['lookahead']);
        $this->assertEquals(1, $conflict['rule']->getNumber());
        $this->assertEquals(Grammar::SHIFT, $conflict['resolution']);

        $conflict = $conflicts[2];

        $this->assertEquals(4, $conflict['state']);
        $this->assertEquals(Parser::EOF_TOKEN_TYPE, $conflict['lookahead']);
        $this->assertEquals(1, $conflict['rules'][0]->getNumber());
        $this->assertEquals(2, $conflict['rules'][1]->getNumber());
        $this->assertEquals(Grammar::LONGER_REDUCE, $conflict['resolution']);

        $conflict = $conflicts[3];

        $this->assertEquals(4, $conflict['state']);
        $this->assertEquals('b', $conflict['lookahead']);
        $this->assertEquals(2, $conflict['rule']->getNumber());
        $this->assertEquals(Grammar::SHIFT, $conflict['resolution']);
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
