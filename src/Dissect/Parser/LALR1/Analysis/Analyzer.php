<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\Analysis\Exception\ReduceReduceConflictException;
use Dissect\Parser\LALR1\Analysis\Exception\ShiftReduceConflictException;
use Dissect\Parser\LALR1\Analysis\KernelSet\KernelSet;
use Dissect\Parser\Grammar;
use Dissect\Parser\Parser;
use Dissect\Util\Util;
use SplQueue;

/**
 * Performs a grammar analysis and returns
 * the result.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Analyzer
{
    /**
     * Performs a grammar analysis.
     *
     * @param \Dissect\Parser\Grammar $grammar The grammar to analyse.
     *
     * @return \Dissect\Parser\LALR1\Analysis\AnalysisResult The result ofthe analysis.
     */
    public function analyze(Grammar $grammar)
    {
        $automaton = $this->buildAutomaton($grammar);
        list($parseTable, $conflicts) = $this->buildParseTable($automaton, $grammar);

        return new AnalysisResult($parseTable, $automaton, $conflicts);
    }

    /**
     * Builds the handle-finding FSA from the grammar.
     *
     * @param \Dissect\Parser\Grammar $grammar The grammar.
     *
     * @return \Dissect\Parser\LALR1\Analysis\Automaton The resulting automaton.
     */
    protected function buildAutomaton(Grammar $grammar)
    {
        // the eventual automaton
        $automaton = new Automaton();

        // the queue of states that need processing
        $queue = new SplQueue();

        // the BST for state kernels
        $kernelSet = new KernelSet();

        // rules grouped by their name
        $groupedRules = $grammar->getGroupedRules();

        // FIRST sets of nonterminals
        $firstSets = $this->calculateFirstSets($groupedRules);

        // keeps a list of tokens that need to be pumped
        // through the automaton
        $pumpings = array();

        // the item from which the whole automaton
        // is derived
        $initialItem = new Item($grammar->getStartRule(), 0);

        // construct the initial state
        $state = new State($kernelSet->insert(array(
            array($initialItem->getRule()->getNumber(), $initialItem->getDotIndex()),
        )), array($initialItem));

        // the initial item automatically has EOF
        // as its lookahead
        $pumpings[] = array($initialItem, array(Parser::EOF_TOKEN_TYPE));

        $queue->enqueue($state);
        $automaton->addState($state);

        while (!$queue->isEmpty()) {
            $state = $queue->dequeue();

            // items of this state are grouped by
            // the active component to calculate
            // transitions easily
            $groupedItems = array();

            // calculate closure
            $added = array();
            $currentItems = $state->getItems();
            for ($x = 0; $x < count($currentItems); $x++) {
                $item = $currentItems[$x];

                if (!$item->isReduceItem()) {
                    $component = $item->getActiveComponent();
                    $groupedItems[$component][] = $item;

                    // if nonterminal
                    if ($grammar->hasNonterminal($component)) {

                        // calculate lookahead
                        $lookahead = array();
                        $cs = $item->getUnrecognizedComponents();

                        foreach ($cs as $i => $c) {
                            if (!$grammar->hasNonterminal($c)) {
                                // if terminal, add it and break the loop
                                $lookahead = Util::union($lookahead, array($c));

                                break;
                            } else {
                                // if nonterminal
                                $new = $firstSets[$c];

                                if (!in_array(Grammar::EPSILON, $new)) {
                                    // if the component doesn't derive
                                    // epsilon, merge FIRST sets and break
                                    $lookahead = Util::union($lookahead, $new);

                                    break;
                                } else {
                                    // if it does

                                    if ($i < (count($cs) - 1)) {
                                        // if more components ahead, remove epsilon
                                        unset($new[array_search(Grammar::EPSILON, $new)]);
                                    }

                                    // and continue the loop
                                    $lookahead = Util::union($lookahead, $new);
                                }
                            }
                        }

                        // two items are connected if the unrecognized
                        // part of rule 1 derives epsilon
                        $connect = false;

                        // only store the pumped tokens if there
                        // actually is an unrecognized part
                        $pump = true;

                        if (empty($lookahead)) {
                            $connect = true;
                            $pump = false;
                        } else {
                            if (in_array(Grammar::EPSILON, $lookahead)) {
                                unset($lookahead[array_search(Grammar::EPSILON, $lookahead)]);

                                $connect = true;
                            }
                        }

                        foreach ($groupedRules[$component] as $rule) {
                            if (!in_array($component, $added)) {
                                // if $component hasn't yet been expaned,
                                // create new items for it
                                $newItem = new Item($rule, 0);

                                $currentItems[] = $newItem;
                                $state->add($newItem);

                            } else {
                                // if it was expanded, each original
                                // rule might bring new lookahead tokens,
                                // so get the rule from the current state
                                $newItem = $state->get($rule->getNumber(), 0);
                            }

                            if ($connect) {
                                $item->connect($newItem);
                            }

                            if ($pump) {
                                $pumpings[] = array($newItem, $lookahead);
                            }
                        }
                    }

                    // mark the component as processed
                    $added[] = $component;
                }
            }

            // calculate transitions
            foreach ($groupedItems as $thisComponent => $theseItems) {
                $newKernel = array();

                foreach ($theseItems as $thisItem) {
                    $newKernel[] = array(
                        $thisItem->getRule()->getNumber(),
                        $thisItem->getDotIndex() + 1,
                    );
                }

                $num = $kernelSet->insert($newKernel);

                if ($automaton->hasState($num)) {
                    // the state already exists
                    $automaton->addTransition($state->getNumber(), $thisComponent, $num);

                    // extract the connected items from the target state
                    $nextState = $automaton->getState($num);

                    foreach ($theseItems as $thisItem) {
                        $thisItem->connect(
                            $nextState->get(
                                $thisItem->getRule()->getNumber(),
                                $thisItem->getDotIndex() + 1
                            )
                        );
                    }
                } else {
                    // new state needs to be created
                    $newState = new State($num, array_map(function (Item $i) {
                        $new = new Item($i->getRule(), $i->getDotIndex() + 1);

                        // connect the two items
                        $i->connect($new);

                        return $new;
                    }, $theseItems));

                    $automaton->addState($newState);
                    $queue->enqueue($newState);

                    $automaton->addTransition($state->getNumber(), $thisComponent, $num);
                }
            }
        }

        // pump all the lookahead tokens
        foreach ($pumpings as $pumping) {
            $pumping[0]->pumpAll($pumping[1]);
        }

        return $automaton;
    }

    /**
     * Encodes the handle-finding FSA as a LR parse table.
     *
     * @param \Dissect\Parser\LALR1\Analysis\Automaton $automaton
     *
     * @return array The parse table.
     */
    protected function buildParseTable(Automaton $automaton, Grammar $grammar)
    {
        $conflictsMode = $grammar->getConflictsMode();
        $conflicts = array();
        $errors = array();

        // initialize the table
        $table = array(
            'action' => array(),
            'goto' => array(),
        );

        foreach ($automaton->getTransitionTable() as $num => $transitions) {
            foreach ($transitions as $trigger => $destination) {
                if (!$grammar->hasNonterminal($trigger)) {
                    // terminal implies shift
                    $table['action'][$num][$trigger] = $destination;
                } else {
                    // nonterminal goes in the goto table
                    $table['goto'][$num][$trigger] = $destination;
                }
            }
        }

        foreach ($automaton->getStates() as $num => $state) {
            if (!isset($table['action'][$num])) {
                $table['action'][$num] = array();
            }

            foreach ($state->getItems() as $item) {
                if ($item->isReduceItem()) {
                    $ruleNumber = $item->getRule()->getNumber();

                    foreach ($item->getLookahead() as $token) {
                        if (isset($errors[$num]) && isset($errors[$num][$token])) {
                            // there was a previous conflict resolved as an error
                            // entry for this token.

                            continue;
                        }

                        if (array_key_exists($token, $table['action'][$num])) {
                            // conflict
                            $instruction = $table['action'][$num][$token];

                            if ($instruction > 0) {
                                if ($conflictsMode & Grammar::OPERATORS) {
                                    if ($grammar->hasOperator($token)) {
                                        $operatorInfo = $grammar->getOperatorInfo($token);

                                        $rulePrecedence = $item->getRule()->getPrecedence();

                                        // unless the rule has given precedence
                                        if ($rulePrecedence === null) {
                                            foreach (array_reverse($item->getRule()->getComponents()) as $c) {
                                                // try to extract it from the rightmost terminal
                                                if ($grammar->hasOperator($c)) {
                                                    $ruleOperatorInfo = $grammar->getOperatorInfo($c);
                                                    $rulePrecedence = $ruleOperatorInfo['prec'];

                                                    break;
                                                }
                                            }
                                        }

                                        if ($rulePrecedence !== null) {
                                            // if we actually have a rule precedence

                                            $tokenPrecedence = $operatorInfo['prec'];

                                            if ($rulePrecedence > $tokenPrecedence) {
                                                // if the rule precedence is higher, reduce
                                                $table['action'][$num][$token] = -$ruleNumber;
                                            } elseif ($rulePrecedence < $tokenPrecedence) {
                                                // if the token precedence is higher, shift
                                                // (i.e. don't modify the table)
                                            } else {
                                                // precedences are equal, let's turn to associativity
                                                $assoc = $operatorInfo['assoc'];

                                                if ($assoc === Grammar::RIGHT) {
                                                    // if right-associative, shift
                                                    // (i.e. don't modify the table)
                                                } elseif ($assoc === Grammar::LEFT) {
                                                    // if left-associative, reduce
                                                    $table['action'][$num][$token] = -$ruleNumber;
                                                } elseif ($assoc === Grammar::NONASSOC) {
                                                    // the token is nonassociative.
                                                    // this actually means an input error, so
                                                    // remove the shift entry from the table
                                                    // and mark this as an explicit error
                                                    // entry
                                                    unset($table['action'][$num][$token]);
                                                    $errors[$num][$token] = true;
                                                }
                                            }

                                            continue; // resolved the conflict, phew
                                        }

                                        // we couldn't calculate the precedence => the conflict was not resolved
                                        // move along.
                                    }
                                }

                                // s/r
                                if ($conflictsMode & Grammar::SHIFT) {
                                    $conflicts[] = array(
                                        'state' => $num,
                                        'lookahead' => $token,
                                        'rule' => $item->getRule(),
                                        'resolution' => Grammar::SHIFT,
                                    );

                                    continue;
                                } else {
                                    throw new ShiftReduceConflictException(
                                        $num,
                                        $item->getRule(),
                                        $token,
                                        $automaton
                                    );
                                }
                            } else {
                                // r/r

                                $originalRule = $grammar->getRule(-$instruction);
                                $newRule = $item->getRule();

                                if ($conflictsMode & Grammar::LONGER_REDUCE) {

                                    $count1 = count($originalRule->getComponents());
                                    $count2 = count($newRule->getComponents());

                                    if ($count1 > $count2) {
                                        // original rule is longer
                                        $resolvedRules = array($originalRule, $newRule);

                                        $conflicts[] = array(
                                            'state' => $num,
                                            'lookahead' => $token,
                                            'rules' => $resolvedRules,
                                            'resolution' => Grammar::LONGER_REDUCE,
                                        );

                                        continue;
                                    } elseif ($count2 > $count1) {
                                        // new rule is longer
                                        $table['action'][$num][$token] = -$ruleNumber;
                                        $resolvedRules = array($newRule, $originalRule);

                                        $conflicts[] = array(
                                            'state' => $num,
                                            'lookahead' => $token,
                                            'rules' => $resolvedRules,
                                            'resolution' => Grammar::LONGER_REDUCE,
                                        );

                                        continue;
                                    }
                                }

                                if ($conflictsMode & Grammar::EARLIER_REDUCE) {
                                    if (-$instruction < $ruleNumber) {
                                        // original rule was earlier
                                        $resolvedRules = array($originalRule, $newRule);

                                        $conflicts[] = array(
                                            'state' => $num,
                                            'lookahead' => $token,
                                            'rules' => $resolvedRules,
                                            'resolution' => Grammar::EARLIER_REDUCE,
                                        );

                                        continue;
                                    } else {
                                        // new rule was earlier
                                        $table['action'][$num][$token] = -$ruleNumber;

                                        $conflicts[] = array(
                                            'state' => $num,
                                            'lookahead' => $token,
                                            'rules' => $resolvedRules,
                                            'resolution' => Grammar::EARLIER_REDUCE,
                                        );
                                        $resolvedRules = array($newRule, $originalRule);

                                        continue;
                                    }
                                }

                                // everything failed, throw an exception
                                throw new ReduceReduceConflictException(
                                    $num,
                                    $originalRule,
                                    $newRule,
                                    $token,
                                    $automaton
                                );
                            }
                        }

                        $table['action'][$num][$token] = -$ruleNumber;
                    }
                }
            }
        }

        return array($table, $conflicts);
    }

    /**
     * Calculates the FIRST sets of all nonterminals.
     *
     * @param array $rules The rules grouped by the LHS.
     *
     * @return array Calculated FIRST sets.
     */
    protected function calculateFirstSets(array $rules)
    {
        // initialize
        $firstSets = array();

        foreach (array_keys($rules) as $lhs) {
            $firstSets[$lhs] = array();
        }

        do {
            $changes = false;

            foreach ($rules as $lhs => $ruleArray) {
                foreach ($ruleArray as $rule) {
                    $components = $rule->getComponents();
                    $new = array();

                    if (empty($components)) {
                        $new = array(Grammar::EPSILON);
                    } else {
                        foreach ($components as $i => $component) {
                            if (array_key_exists($component, $rules)) {
                                // if nonterminal, copy its FIRST set to
                                // this rule's first set
                                $x = $firstSets[$component];

                                if (!in_array(Grammar::EPSILON, $x)) {
                                    // if the component doesn't derive
                                    // epsilon, merge the first sets and
                                    // we're done
                                    $new = Util::union($new, $x);

                                    break;
                                } else {
                                    // if all components derive epsilon,
                                    // the rule itself derives epsilon

                                    if ($i < (count($components) - 1)) {
                                        // more components ahead, remove epsilon
                                        unset($x[array_search(Grammar::EPSILON, $x)]);
                                    }

                                    $new = Util::union($new, $x);
                                }
                            } else {
                                // if terminal, simply add it the the FIRST set
                                // and we're done
                                $new = Util::union($new, array($component));

                                break;
                            }
                        }
                    }

                    if (Util::different($new, $firstSets[$lhs])) {
                        $firstSets[$lhs] = Util::union($firstSets[$lhs], $new);

                        $changes = true;
                    }
                }
            }
        } while ($changes);

        return $firstSets;
    }
}
