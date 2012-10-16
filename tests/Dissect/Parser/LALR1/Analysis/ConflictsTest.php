<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\Analysis\Exception\ReduceReduceConflictException;
use Dissect\Parser\LALR1\Analysis\Exception\ShiftReduceConflictException;
use Dissect\Parser\Grammar;
use PHPUnit_Framework_TestCase;

class ConflictsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function analyzerShouldThrowAnExceptionOnAReduceReduceConflict()
    {
        $grammar = new Grammar();
        $grammar->rule('S', array('a', 'a', 'A'));
        $grammar->rule('S', array('a', 'b', 'B'));

        $grammar->rule('A', array('C', 'a'));
        $grammar->rule('A', array('D', 'b'));

        $grammar->rule('B', array('C', 'b'));
        $grammar->rule('B', array('D', 'a'));

        $grammar->rule('C', array('E'));
        $grammar->rule('D', array('E'));

        $grammar->rule('E', array());

        $grammar->start('S');

        $analyzer = new Analyzer();

        try {
            $table = $analyzer->createParseTable($grammar);
            $this->fail('Expected a reduce/reduce conflict exception.');
        } catch (ReduceReduceConflictException $e) {
        }
    }

    /**
     * @test
     */
    public function analyzerShouldThrowAnExceptionOnAShiftReduceConflict()
    {
        $grammar = new Grammar();
        $grammar->rule('Exp', array('Exp', '+', 'Exp'));
        $grammar->start('Exp');

        $analyzer = new Analyzer();

        try {
            $table = $analyzer->createParseTable($grammar);
            $this->fail('Expected a shift/reduce conflict exception.');
        } catch (ShiftReduceConflictException $e) {
        }
    }
}
