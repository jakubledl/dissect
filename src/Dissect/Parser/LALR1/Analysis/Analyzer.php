<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;

/**
 * Analyzes a grammar and produces a LALR(1) parse table.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Analyzer
{
    /**
     * Creates the parse table for the grammar.
     *
     * @param \Dissect\Parser\Grammar $grammar The grammar.
     *
     * @return array The parse table.
     */
    public function createParseTable(Grammar $grammar)
    {
        $calculator = new ItemSetCalculator($grammar);
        list ($itemSets, $transitionTable) = $calculator->calculateItemSets();

        $calculator = new ExtendedGrammarCalculator($itemSets, $transitionTable,
            $grammar->getNonterminals());
        list ($extendedRules, $extendedNonterminals) =
            $calculator->calculateExtendedRules();

        $calculator = new FollowSetsCalculator($extendedRules, $extendedNonterminals);
        $followSets = $calculator->calculateFollowSets();

        $calculator = new ParseTableCalculator(
            $itemSets,
            $grammar->getRules(),
            $extendedRules,
            $followSets,
            $transitionTable,
            $grammar->getNonterminals(),
            $grammar->getConflictsMode()
        );

        return $calculator->calculateParseTable($grammar->getStartRule()->getNumber());
    }
}
