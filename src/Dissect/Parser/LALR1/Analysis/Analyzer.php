<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;
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

        // construct the initial state
        $state = new State($nextStateNumber++, array(
            new Item($grammar->getStartRule(), 0),
        ));

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

                    if (!in_array($component, $added) && in_array($component, $nonterminals)) {
                        // if not processed

                        foreach (array_map(function ($rule) {
                            return new Item($rule, 0);
                        }, $groupedRules[$component]) as $newItem) {
                            $currentItems[] = $newItem;
                            $state->add($newItem);
                        }

                        $added[] = $component;
                    }
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
                    $match = true;

                    foreach ($currentOrigin as $rule => &$positions) {
                        sort($positions);
                        $match = (isset($map[$rule]) && $map[$rule] == $positions) && $match;
                    }

                    if ($match) {
                        $n = $number;
                        break;
                    }
                }

                if ($n === null) {
                    $num = $nextStateNumber++;
                    $newState = new State($num, array_map(function (Item $i) {
                        return new Item($i->getRule(), $i->getDotIndex() + 1);
                    }, $items));

                    $automaton->addState($newState);
                    $queue->enqueue($newState);

                    $origins[$num] = $currentOrigin;

                    $automaton->addTransition($state->getNumber(), $component, $num);
                } else {
                    $automaton->addTransition($state->getNumber(), $component, $n);
                }
            }
        }

        return $automaton;
    }
}
