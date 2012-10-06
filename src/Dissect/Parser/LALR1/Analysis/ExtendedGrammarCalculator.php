<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;

/**
 * Augments the grammar with notion of which
 * nonterminals cause state transitions to which
 * states.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ExtendedGrammarCalculator
{
    /**
     * @var \Dissect\Parser\LALR1\ItemSet[]
     */
    protected $itemSets;

    /**
     * @var array
     */
    protected $transitionTable;

    /**
     * @var string[]
     */
    protected $nonterminals;

    /**
     * Constructor.
     *
     * @param array $itemSets The item sets.
     * @param array $transitionTable The transition table.
     */
    public function __construct(array $itemSets, array $transitionTable, array $nonterminals)
    {
        $this->itemSets = $itemSets;
        $this->transitionTable = $transitionTable;
        $this->nonterminals = $nonterminals;
    }

    /**
     * Returns the set of extended grammar rules.
     *
     * @return array An array with the rules at position 0 and a set of
     * extended nonterminals at position 1.
     */
    public function calculateExtendedRules()
    {
        $nextRuleNumber = 0;
        $extendedNonterminals = array();
        $extendedRules = array();

        foreach ($this->itemSets as $itemSet) {
            foreach ($itemSet->all() as $item) {
                if ($item->getDotIndex() === 0) {
                    // only process items with dot at the beginning
                    $rule = $item->getRule();
                    $number = $nextRuleNumber++;
                    $originSetNumber = $itemSet->getNumber();
                    $name = $rule->getName();

                    if ($name === Grammar::START_RULE_NAME) {
                        // from initial set, transition by $start is always to
                        // accepting state
                        $destSetNumber = '$';
                    } else {
                        // lookup the next state in the transition table
                        $destSetNumber = $this->transitionTable[$itemSet->getNumber()][$name];
                    }

                    // give the rule a distinct name based on its
                    // origin and destination states
                    $newName = $originSetNumber . $name . $destSetNumber;

                    // and add it to extended nonterminals
                    $extendedNonterminals[] = $newName;

                    // calculate extended components:
                    // start in the origin state
                    $finalSetNumber = $originSetNumber;
                    $components = array();

                    foreach ($rule->getComponents() as $c) {
                        // lookup the new state
                        $newSetNumber = $this->transitionTable[$finalSetNumber][$c];

                        // only nonterminals have to be distinguished
                        if (in_array($c, $this->nonterminals)) {
                            $c = $finalSetNumber . $c . $newSetNumber;
                        }

                        $components[] = $c;
                        $finalSetNumber = $newSetNumber;
                    }

                    $extendedRules[$number] = new ExtendedRule(
                        $number,
                        $rule->getNumber(),
                        $newName,
                        $components,
                        $name,
                        $finalSetNumber
                    );
                }
            }
        }

        return array($extendedRules, $extendedNonterminals);
    }
}
