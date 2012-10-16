<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;
use Dissect\Parser\Parser;
use PHPUnit_Framework_TestCase;

class AnalysisTest extends PHPUnit_Framework_TestCase
{
    protected $grammar;
    protected $itemSets;
    protected $transitionTable;
    protected $extendedRules;
    protected $extendedNonterminals;
    protected $followSets;

    protected function setUp()
    {
        $this->grammar = new Grammar();

        $this->grammar->rule('A', array('a', 'B', 'c'));
        $this->grammar->rule('B', array()); // an epsilon rule
        $this->grammar->start('A');

        // This tiny grammar results in these item sets:
        //
        // i0:
        //   $start -> . A
        //   A -> . a B c
        //
        // i1:
        //   $start -> A .
        //
        // i2:
        //   A -> a . B c
        //   B -> .
        //
        // i3:
        //   A -> a B . c
        //
        // i4:
        //   A -> a B c .

        // this transition table:
        //
        // +----+---+---+---+---+
        // |    | A | a | B | c |
        // +----+---+---+---+---+
        // | i0 | 1 | 2 |   |   |
        // +----+---+---+---+---+
        // | i1 |   |   |   |   |
        // +----+---+---+---+---+
        // | i2 |   |   | 3 |   |
        // +----+---+---+---+---+
        // | i3 |   |   |   | 4 |
        // +----+---+---+---+---+
        // | i4 |   |   |   |   |
        // +----+---+---+---+---+

        // this extended grammar:
        //
        // 0. 0$start$ -> 0A1 (final state 1)
        // 1. 0A1 -> a 2B3 c  (final state 4)
        // 2. 2B3 ->          (epsilon, final state 2)

        // these FOLLOW sets:
        //
        // FOLLOW(0$start$) = { $eof }
        // FOLLOW(0A1)      = { $eof }
        // FOLLOW(2B3)      = { c }

        // and this parse table:
        //
        // +----------------------+-------+
        // | ACTION               | GOTO  |
        // +-----+----+----+------+-------+
        // |     | a  | c  | $eof | A | B |
        // +-----+----+----+------+---+---+
        // | 0   | s2 |    |      | 1 |   |
        // +-----+----+----+------+---+---+
        // | 1   |    |    | acc  |   |   |
        // +-----+----+----+------+---+---+
        // | 2   |    | r2 |      |   | 3 |
        // +-----+----+----+------+---+---+
        // | 3   |    | s4 |      |   |   |
        // +-----+----+----+------+---+---+
        // | 4   |    |    | r1   |   |   |
        // +-----+----+----+------+---+---+

        $calculator = new ItemSetCalculator($this->grammar);
        list ($this->itemSets, $this->transitionTable) = $calculator->calculateItemSets();

        $calculator = new ExtendedGrammarCalculator($this->itemSets, $this->transitionTable,
            $this->grammar->getNonterminals());

        list ($this->extendedRules, $this->extendedNonterminals) =
            $calculator->calculateExtendedRules();

        $calculator = new FollowSetsCalculator($this->extendedRules, $this->extendedNonterminals);
        $this->followSets = $calculator->calculateFollowSets();

        $calculator = new ParseTableCalculator(
            $this->itemSets,
            $this->grammar->getRules(),
            $this->extendedRules,
            $this->followSets,
            $this->transitionTable,
            $this->grammar->getNonterminals()
        );

        $this->parseTable = $calculator->calculateParseTable($this->grammar->getStartRule()->getNumber());
    }

    /**
     * @test
     */
    public function itemSetsShouldBeCorrectlyCalculated()
    {
        $this->assertCount(5, $this->itemSets);

        // i0
        $items = $this->itemSets[0]->all();
        $this->assertEquals(Grammar::START_RULE_NAME, $items[0]->getRule()->getName());
        $this->assertEquals(0, $items[0]->getDotIndex());

        $this->assertEquals('A', $items[1]->getRule()->getName());
        $this->assertEquals(0, $items[1]->getDotIndex());

        // i1
        $items = $this->itemSets[1]->all();
        $this->assertEquals(Grammar::START_RULE_NAME, $items[0]->getRule()->getName());
        $this->assertEquals(1, $items[0]->getDotIndex());

        // i2
        $items = $this->itemSets[2]->all();
        $this->assertEquals('A', $items[0]->getRule()->getName());
        $this->assertEquals(1, $items[0]->getDotIndex());

        $this->assertEquals('B', $items[1]->getRule()->getName());
        $this->assertEquals(0, $items[1]->getDotIndex());

        // i3
        $items = $this->itemSets[3]->all();
        $this->assertEquals('A', $items[0]->getRule()->getName());
        $this->assertEquals(2, $items[0]->getDotIndex());

        // i4
        $items = $this->itemSets[4]->all();
        $this->assertEquals('A', $items[0]->getRule()->getName());
        $this->assertEquals(3, $items[0]->getDotIndex());
    }

    /**
     * @test
     */
    public function transitionTableShouldBeCorrectlyCalculated()
    {
        $this->assertEquals(array('A' => 1, 'a' => 2), $this->transitionTable[0]);
        $this->assertEquals(array('B' => 3), $this->transitionTable[2]);
        $this->assertEquals(array('c' => 4), $this->transitionTable[3]);
    }

    /**
     * @test
     */
    public function extendedRulesShouldBeCorrectlyCalculated()
    {
        $this->assertCount(3, $this->extendedRules);

        // rule 0
        $rule = $this->extendedRules[0];
        $this->assertEquals(Grammar::START_RULE_NAME, $rule->getOriginalName());
        $this->assertEquals(1, $rule->getFinalSetNumber());

        // rule 1
        $rule = $this->extendedRules[1];
        $this->assertEquals('A', $rule->getOriginalName());
        $this->assertEquals(4, $rule->getFinalSetNumber());
        $this->assertEquals('2B3', $rule->getComponent(1));

        // rule 2
        $rule = $this->extendedRules[2];
        $this->assertEquals('B', $rule->getOriginalName());
        $this->assertEquals(2, $rule->getFinalSetNumber()); // epsilons don't change state
        $this->assertEmpty($rule->getComponents());
    }

    /**
     * @test
     */
    public function extendedNonterminalsShouldBeCorrectlyCalculated()
    {
        $this->assertEquals(array(
            '0$start$',
            '0A1',
            '2B3',
        ), $this->extendedNonterminals);
    }

    /**
     * @test
     */
    public function followSetsShouldBeCorrectlyCalculated()
    {
        $this->assertEquals(array(Parser::EOF_TOKEN_TYPE), $this->followSets['0$start$']);
        $this->assertEquals(array(Parser::EOF_TOKEN_TYPE), $this->followSets['0A1']);
        $this->assertEquals(array('c'), $this->followSets['2B3']);
    }

    /**
     * @test
     */
    public function parseTableShouldBeCorrectlyCalculated()
    {
        // action table
        $this->assertEquals(2, $this->parseTable['action'][0]['a']);
        $this->assertEquals('acc', $this->parseTable['action'][1][Parser::EOF_TOKEN_TYPE]);
        $this->assertEquals(-2, $this->parseTable['action'][2]['c']);
        $this->assertEquals(4, $this->parseTable['action'][3]['c']);
        $this->assertEquals(-1, $this->parseTable['action'][4][Parser::EOF_TOKEN_TYPE]);

        // goto table
        $this->assertEquals(1, $this->parseTable['goto'][0]['A']);
        $this->assertEquals(3, $this->parseTable['goto'][2]['B']);
    }
}
