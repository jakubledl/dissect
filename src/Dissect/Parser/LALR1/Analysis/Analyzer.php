<?php

namespace Dissect\Parser\LALR1\Analysis;

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

        return new AnalysisResult($automaton);
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
        $automaton = new Automaton();
        $queue = new SplQueue();
        $origins = array();
        $nextStateNumber = 0;
        $nonterminals = $grammar->getNonterminals();
        $groupedRules = $grammar->getGroupedRules();
        $firstSets = $this->calculateFirstSets($groupedRules);
        $pumpings = array();
        $initialItem = new Item($grammar->getStartRule(), 0);

        // construct the initial state
        $state = new State($nextStateNumber++, array($initialItem));

        $pumpings[] = array($initialItem, array(Parser::EOF_TOKEN_TYPE));

        $queue->enqueue($state);
        $automaton->addState($state);

        while (!$queue->isEmpty()) {
            $state = $queue->dequeue();
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
                    if (in_array($component, $nonterminals)) {

                        // calculate lookahead
                        $lookahead = array();
                        $cs = $item->getUnrecognizedComponents();

                        foreach ($cs as $i => $c) {
                            if (!in_array($c, $nonterminals)) {
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

                        $connect = false;
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
                                $newItem = new Item($rule, 0);

                                $currentItems[] = $newItem;
                                $state->add($newItem);

                            } else {
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

                    $added[] = $component;
                }
            }

            // calculate transitions
            foreach ($groupedItems as $component => $items) {
                $currentOrigin = array();

                foreach ($items as $item) {
                    $currentOrigin[$item->getRule()->getNumber()][] =
                        $item->getDotIndex();
                }

                $n = null;

                foreach ($origins as $number => $map) {
                    $match = false;

                    foreach ($currentOrigin as $rule => &$positions) {
                        sort($positions);
                        if (isset($map[$rule]) && $map[$rule] == $positions) {
                            $match = true;

                            break;
                        }
                    }

                    if ($match) {
                        $n = $number;
                        break;
                    }
                }

                if ($n === null) {
                    $num = $nextStateNumber++;
                    $newState = new State($num, array_map(function (Item $i) {
                        $newItem = new Item($i->getRule(), $i->getDotIndex() + 1);
                        $i->connect($newItem);

                        return $newItem;
                    }, $items));

                    $automaton->addState($newState);
                    $queue->enqueue($newState);

                    $origins[$num] = $currentOrigin;

                    $automaton->addTransition($state->getNumber(), $component, $num);
                } else {
                    $automaton->addTransition($state->getNumber(), $component, $n);
                    $nextState = $automaton->getState($n);

                    foreach ($items as $item) {
                        $item->connect(
                            $nextState->get(
                                $item->getRule()->getNumber(),
                                $item->getDotIndex() + 1
                            )
                        );
                    }
                }
            }
        }

        foreach ($pumpings as $pumping) {
            $pumping[0]->pumpAll($pumping[1]);
        }

        return $automaton;
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
