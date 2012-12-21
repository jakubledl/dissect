<?php

namespace Dissect\Parser\LALR1\Analysis;

use PHPUnit_Framework_TestCase;
use Dissect\Parser\Grammar;

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
