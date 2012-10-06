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
        $grammar->addRule('S', array('a', 'a', 'A'));
        $grammar->addRule('S', array('a', 'b', 'B'));

        $grammar->addRule('A', array('C', 'a'));
        $grammar->addRule('A', array('D', 'b'));

        $grammar->addRule('B', array('C', 'b'));
        $grammar->addRule('B', array('D', 'a'));

        $grammar->addRule('C', array('E'));
        $grammar->addRule('D', array('E'));

        $grammar->addRule('E', array());

        $grammar->setStartRule('S');

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
        $grammar->addRule('Exp', array('Exp', '+', 'Exp'));
        $grammar->setStartRule('Exp');

        $analyzer = new Analyzer();

        try {
            $table = $analyzer->createParseTable($grammar);
            $this->fail('Expected a shift/reduce conflict exception.');
        } catch (ShiftReduceConflictException $e) {
        }
    }
}
