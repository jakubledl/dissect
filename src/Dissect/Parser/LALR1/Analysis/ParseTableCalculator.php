<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\Analysis\Exception\ShiftReduceConflictException;
use Dissect\Parser\LALR1\Analysis\Exception\ReduceReduceConflictException;
use Dissect\Parser\Grammar;
use Dissect\Parser\Parser;
use Dissect\Util\Util;

/**
 * The final step in the grammar analysis process:
 * given a set of item sets, a set of extended rules,
 * a set of FOLLOW sets for these rules,
 * a state transition table and a set of original nonterminals,
 * it calculates the parse table for the grammar.
 */
class ParseTableCalculator
{
    /**
     * @var array
     */
    protected $itemSets;

    /**
     * @var array
     */
    protected $rules;

    /**
     * @var array
     */
    protected $extendedRules;

    /**
     * @var array
     */
    protected $followSets;

    /**
     * @var array
     */
    protected $transitionTable;

    /**
     * @var array
     */
    protected $nonterminals;

    /**
     * @var int
     */
    protected $conflictsMode;

    /**
     * Constructor.
     *
     * @param array $itemSets The item sets.
     * @param array $rules The original rules (used to build an
     * exception on a parse table conflict).
     * @param array $extendedRules The extended rules.
     * @param array $followSets The follow sets.
     * @param array $transitionTable The transition table.
     * @param array $nonterminals The nonterminals of the original grammar.
     */
    public function __construct(
        array $itemSets,
        array $rules,
        array $extendedRules,
        array $followSets,
        array $transitionTable,
        array $nonterminals,
        $conflictsMode
    )
    {
        $this->itemSets = $itemSets;
        $this->rules = $rules;
        $this->extendedRules = $extendedRules;
        $this->followSets = $followSets;
        $this->transitionTable = $transitionTable;
        $this->nonterminals = $nonterminals;
        $this->conflictsMode = $conflictsMode;
    }

    /**
     * Calculates the parse table using the provided information.
     *
     * @param int $startRuleNumber The number of the start rule.
     *
     * @return array The parse table.
     */
    public function calculateParseTable($startRuleNumber)
    {
        // initialize the table
        $table = array('action' => array(), 'goto' => array());

        foreach ($this->itemSets as $set) {
            foreach ($set->all() as $item) {
                if ($item->getRule()->getNumber() === $startRuleNumber &&
                    $item->isReductionItem()) {

                    // for each item set which has a reduction item with
                    // LHS = start rule, add accept as the action for EOF
                    $table['action'][$set->getNumber()] = array(Parser::EOF_TOKEN_TYPE => 'acc');
                    break;
                } else {
                    $table['action'][$set->getNumber()] = array();
                }
            }
        }

        $reductions = array();
        $count = count($this->extendedRules);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if (!isset($this->extendedRules[$i])) {
                    // rule at $i has already been merged
                    continue 2;
                }

                if (!isset($this->extendedRules[$j])) {
                    // same for $j
                    continue;
                }

                $first = $this->extendedRules[$i];
                $second = $this->extendedRules[$j];

                if ($first->getOriginalNumber() === $second->getOriginalNumber() &&
                    $first->getFinalSetNumber() === $second->getFinalSetNumber()) {

                    // extended rules derived from same original rules
                    // and having the same final state are identical
                    // and can be merged
                    $this->followSets[$first->getName()] = Util::union(
                        $this->followSets[$first->getName()],
                        $this->followSets[$second->getName()]
                    );

                    unset($this->extendedRules[$j]);
                }
            }
        }

        foreach ($this->extendedRules as $rule) {
            if ($rule->getOriginalNumber() !== $startRuleNumber) {
                // traverse remaining rules and build the $reductions array.
                // a reduction is a tuple ($state, $followSet), where
                // $state indicates the state in which the reduction is
                // to be performed and $followSet is the set of tokens
                // which, when found as lookahead, trigger the reduction.
                // the starting rule is skipped, since it already
                // has its accept columns
                $reductions[$rule->getFinalSetNumber()][] = array(
                    $rule->getOriginalNumber(),
                    $this->followSets[$rule->getName()],
                );
            }
        }

        foreach ($this->transitionTable as $itemSetNumber => $instructions) {
            foreach ($instructions as $trigger => $transition) {
                // traverse the transition table. If $trigger is a
                // nonterminal, copy its destination to the GOTO table,
                // if it's a terminal, copy its destination as a shift
                // to the ACTION table.
                if (!in_array($trigger, $this->nonterminals)) {
                    $table['action'][$itemSetNumber][$trigger] = $transition;
                } else {
                    $table['goto'][$itemSetNumber][$trigger] = $transition;
                }
            }
        }

        foreach ($reductions as $state => $reductionSet) {
            foreach ($reductionSet as $reduction) {
                foreach ($reduction[1] as $terminal) {
                    // fill in the reductions

                    if (array_key_exists($terminal, $table['action'][$state])) {
                        // there's conflict
                        $instruction = $table['action'][$state][$terminal];

                        if ($instruction < 0) {
                            if ($this->conflictsMode & Grammar::RR_BY_LONGER_RULE) {
                                $count1 = count($this->rules[-$instruction]->getComponents());
                                $count2 = count($this->rules[$reduction[0]]->getComponents());

                                if ($count1 > $count2) {
                                    // original rule is longer
                                    continue;
                                } elseif ($count1 < $count2) {
                                    // new rule is longer
                                    $table['action'][$state][$terminal] = -$reduction[0];
                                    continue;
                                }
                            }

                            // if the rules have same length or resolving by length is disabled,
                            // try resolving by priority
                            if ($this->conflictsMode & Grammar::RR_BY_EARLIER_RULE) {
                                $num1 = $this->rules[-$instruction]->getNumber();
                                $num2 = $this->rules[$reduction[0]]->getNumber();

                                if ($num1 < $num2) {
                                    // original rule was earlier
                                    continue;
                                } else {
                                    // new rule was earlier
                                    $table['action'][$state][$terminal] = -$reduction[0];
                                    continue;
                                }
                            }

                            // reduce/reduce conflict, throw an exception
                            throw new ReduceReduceConflictException(
                                $this->rules[-$instruction],
                                $this->rules[$reduction[0]],
                                $terminal
                            );
                        } else {
                            // if s/r resolving is enabled
                            if ($this->conflictsMode & Grammar::SR_BY_SHIFT) {
                                continue;
                            }

                            throw new ShiftReduceConflictException(
                                $this->rules[$reduction[0]],
                                $terminal
                            );
                        }
                    }

                    $table['action'][$state][$terminal] = -$reduction[0];
                }
            }
        }

        return $table;
    }
}
