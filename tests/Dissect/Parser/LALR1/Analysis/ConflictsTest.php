<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\Analysis\Exception\ReduceReduceConflictException;
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
    public function analyzerShouldByDefaultChooseToShiftOnAnShiftReduceConflict()
    {
        $grammar = new Grammar();
        $grammar->rule('Exp', array('Exp', '+', 'Exp'));
        $grammar->rule('Exp', array('int'));
        $grammar->start('Exp');

        $analyzer = new Analyzer();

        $table = $analyzer->createParseTable($grammar);

        $this->assertEquals(3, $table['action'][4]['+']);
    }

    /**
     * @test
     */
    public function analyzerShouldChooseToReduceByLongerRuleWhenSpecified()
    {
        $grammar = new Grammar();
        $grammar->rule('S', array('S', 'S', 'S'));
        $grammar->rule('S', array('S', 'S'));
        $grammar->rule('S', array('b'));
        $grammar->start('S');

        $grammar->resolve(Grammar::SR_BY_SHIFT | Grammar::RR_BY_LONGER_RULE);

        $analyzer = new Analyzer();

        $table = $analyzer->createParseTable($grammar);

        $this->assertEquals(-1, $table['action'][4]['$eof']);
    }

    /**
     * @test
     */
    public function analyzerShouldChooseToReduceByEarlierRuleWhenSpecified()
    {
        $grammar = new Grammar();
        $grammar->rule('S', array('a', 'B', 'c'));
        $grammar->rule('S', array('a', 'C', 'c'));
        $grammar->rule('B', array('b'));
        $grammar->rule('C', array('b'));
        $grammar->start('S');

        $grammar->resolve(Grammar::RR_BY_EARLIER_RULE);

        $analyzer = new Analyzer();

        $table = $analyzer->createParseTable($grammar);

        $this->assertEquals(-3, $table['action'][5]['c']);
    }
}
